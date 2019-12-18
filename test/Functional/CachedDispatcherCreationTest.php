<?php
declare(strict_types=1);

namespace FastRoute\Test\Functional;

use FastRoute\Caching\SimpleFileCache;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;
use function FastRoute\cachedDispatcher;
use function file_exists;
use function unlink;

final class CachedDispatcherCreationTest extends TestCase
{
    private const OPTIONS = ['cacheFile' => __DIR__ . '/router.php'];

    /** @after */
    public function removeCacheFile(): void
    {
        if (! file_exists(self::OPTIONS['cacheFile'])) {
            return;
        }

        unlink(self::OPTIONS['cacheFile']);
    }

    /** @test */
    public function routeLoaderShouldAlwaysBeCalledWhenCacheIsDisabled(): void
    {
        $calls   = 0;
        $loader  = $this->createSimpleLoader($calls);
        $options = self::OPTIONS + ['cacheDisabled' => true];

        cachedDispatcher($loader, $options);
        cachedDispatcher($loader, $options);
        cachedDispatcher($loader, $options);

        self::assertSame(3, $calls);
    }

    /** @test */
    public function routeLoaderShouldBeCalledOnlyOnceWhenUsingCache(): void
    {
        $calls  = 0;
        $loader = $this->createSimpleLoader($calls);

        cachedDispatcher($loader, self::OPTIONS);
        cachedDispatcher($loader, self::OPTIONS);
        cachedDispatcher($loader, self::OPTIONS);

        self::assertSame(1, $calls);
    }

    /** @test */
    public function customDriverCanBeSpecifiedEvenWithCustomCacheKey(): void
    {
        $calls   = 0;
        $loader  = $this->createSimpleLoader($calls);
        $options = ['cacheDriver' => new SimpleFileCache(__DIR__), 'cacheKey' => 'router.php'];

        cachedDispatcher($loader, $options);
        cachedDispatcher($loader, $options);
        cachedDispatcher($loader, $options);

        self::assertSame(1, $calls);
    }

    private function createSimpleLoader(int &$calls): callable
    {
        return static function (RouteCollector $routes) use (&$calls): void {
            ++$calls;

            $routes->addRoute('GET', '/', 'testing');
        };
    }
}
