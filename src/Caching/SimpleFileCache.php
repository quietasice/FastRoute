<?php
declare(strict_types=1);

namespace FastRoute\Caching;

use LogicException;
use Psr\SimpleCache\CacheInterface;
use function assert;
use function chmod;
use function file_put_contents;
use function is_dir;
use function is_string;
use function is_writable;
use function mkdir;
use function rename;
use function restore_error_handler;
use function rtrim;
use function set_error_handler;
use function tempnam;
use function unlink;
use function var_export;
use const DIRECTORY_SEPARATOR;

final class SimpleFileCache implements CacheInterface
{
    private const DIRECTORY_PERMISSIONS = 0775;
    private const FILE_PERMISSIONS = 0664;

    /**
     * This is cached in a local static variable to avoid instantiating a closure each time we need an empty handler
     *
     * @var callable
     */
    private static $emptyErrorHandler;

    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        self::$emptyErrorHandler = self::$emptyErrorHandler ?? static function (): void {
        };
    }

    /** @inheritDoc */
    public function get($key, $default = null)
    {
        return self::readFileContents($this->directory . $key)['data'] ?? $default;
    }

    /** @inheritDoc */
    public function set($key, $value, $ttl = null): bool
    {
        if ($ttl !== null) {
            throw new LogicException('TTL handling is not implemented. Use a full PSR-16 driver if you need support for that');
        }

        return self::writeToFile(
            $this->directory,
            $key,
            '<?php return ' . var_export(['data' => $value], true) . ';'
        );
    }

    private static function writeToFile(string $directory, string $filename, string $content): bool
    {
        if (! self::createDirectoryIfNeeded($directory) || ! is_writable($directory)) {
            return false;
        }

        set_error_handler(self::$emptyErrorHandler);

        $tmpFile = tempnam($directory, 'swap');
        assert(is_string($tmpFile));

        chmod($tmpFile, self::FILE_PERMISSIONS);

        if (file_put_contents($tmpFile, $content) !== false) {
            chmod($tmpFile, self::FILE_PERMISSIONS);

            if (rename($tmpFile, $directory . $filename)) {
                restore_error_handler();

                return true;
            }

            unlink($tmpFile);
        }

        restore_error_handler();

        return false;
    }

    private static function createDirectoryIfNeeded(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        set_error_handler(self::$emptyErrorHandler);
        $created = mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
        restore_error_handler();

        return $created !== false || is_dir($directory);
    }

    /** @inheritDoc */
    public function delete($key)
    {
        throw new LogicException(__METHOD__ . ' is not implemented. Use a full PSR-16 driver if you need support for that');
    }

    public function clear(): bool
    {
        throw new LogicException(__METHOD__ . ' is not implemented. Use a full PSR-16 driver if you need support for that');
    }

    /** @inheritDoc */
    public function getMultiple($keys, $default = null): iterable
    {
        throw new LogicException(__METHOD__ . ' is not implemented. Use a full PSR-16 driver if you need support for that');
    }

    /** @inheritDoc */
    public function setMultiple($values, $ttl = null): bool
    {
        throw new LogicException(__METHOD__ . ' is not implemented. Use a full PSR-16 driver if you need support for that');
    }

    /** @inheritDoc */
    public function deleteMultiple($keys)
    {
        throw new LogicException(__METHOD__ . ' is not implemented. Use a full PSR-16 driver if you need support for that');
    }

    /** @inheritDoc */
    public function has($key): bool
    {
        throw new LogicException(__METHOD__ . ' is not implemented. Use a full PSR-16 driver if you need support for that');
    }

    /** @return array<string, mixed>|null */
    private static function readFileContents(string $filename): ?array
    {
        // note: error suppression is still faster than `file_exists`, `is_file` and `is_readable`
        set_error_handler(self::$emptyErrorHandler);
        $value = include $filename;
        restore_error_handler();

        if (! isset($value['data'])) {
            return null;
        }

        return $value;
    }
}
