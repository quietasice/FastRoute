<?php
declare(strict_types=1);

namespace FastRoute\Caching;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use function assert;
use function dirname;
use function is_array;

final class Loader
{
    /** @var CacheInterface */
    private $driver;

    /** @var string */
    private $cacheKey;

    private function __construct(CacheInterface $driver, string $cacheKey)
    {
        $this->driver   = $driver;
        $this->cacheKey = $cacheKey;
    }

    /** @param array<string, mixed> $options */
    public static function fromOptions(array $options): self
    {
        $key = self::resolveCacheKey($options);

        if ($options['cacheDisabled'] === true) {
            return new self(new NullCache(), $key);
        }

        if (! isset($options['cacheFile'])) {
            throw new LogicException('Must specify "cacheFile" option');
        }

        return new self(new SimpleFileCache(dirname($options['cacheFile'])), $key);
    }

    /** @param array<string, mixed> $options */
    private static function resolveCacheKey(array $options): string
    {
        if (! isset($options['cacheKey'])) {
            throw new LogicException('Must specify "cacheKey" option');
        }

        return $options['cacheKey'];
    }

    /** @param array<string, mixed> $options */
    public function load(array $options, callable $routeDefinitionCallback): Dispatcher
    {
        $loader = static function () use ($options, $routeDefinitionCallback): array {
            $routeCollector = new $options['routeCollector'](
                new $options['routeParser'](), new $options['dataGenerator']()
            );
            assert($routeCollector instanceof RouteCollector);

            $routeDefinitionCallback($routeCollector);

            return $routeCollector->getData();
        };

        return new $options['dispatcher']($this->fetchDispatchData($loader));
    }

    /** @return array<mixed> */
    private function fetchDispatchData(callable $loader): array
    {
        $dispatchData = $this->driver->get($this->cacheKey, $loader);

        if ($dispatchData === $loader) {
            $dispatchData = $loader();
            $this->driver->set($this->cacheKey, $dispatchData);

            return $dispatchData;
        }

        if (! is_array($dispatchData)) {
            throw new RuntimeException('Invalid cache information in entry "' . $this->cacheKey . '"');
        }

        return $dispatchData;
    }
}
