<?php
declare(strict_types=1);

namespace FastRoute\Caching;

use Psr\SimpleCache\CacheInterface;

final class NullCache implements CacheInterface
{
    /** @inheritDoc */
    public function get($key, $default = null)
    {
        return $default;
    }

    /** @inheritDoc */
    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function delete($key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function getMultiple($keys, $default = null): array
    {
        return [];
    }

    /** @inheritDoc */
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function has($key): bool
    {
        return false;
    }
}
