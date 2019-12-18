<?php
declare(strict_types=1);

namespace FastRoute;

use FastRoute\Caching\Loader;
use function assert;
use function basename;
use function function_exists;

if (! function_exists('FastRoute\simpleDispatcher')) {
    /**
     * @param array<string, mixed> $options
     */
    function simpleDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options += [
            'routeParser' => RouteParser\Std::class,
            'dataGenerator' => DataGenerator\GroupCountBased::class,
            'dispatcher' => Dispatcher\GroupCountBased::class,
            'routeCollector' => RouteCollector::class,
        ];

        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'](), new $options['dataGenerator']()
        );
        assert($routeCollector instanceof RouteCollector);
        $routeDefinitionCallback($routeCollector);

        return new $options['dispatcher']($routeCollector->getData());
    }

    /**
     * @param array<string, mixed> $options
     */
    function cachedDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options += [
            'routeParser' => RouteParser\Std::class,
            'dataGenerator' => DataGenerator\GroupCountBased::class,
            'dispatcher' => Dispatcher\GroupCountBased::class,
            'routeCollector' => RouteCollector::class,
            'cacheDisabled' => false,
        ];

        if (! isset($options['cacheKey']) && isset($options['cacheFile'])) {
            $options['cacheKey'] = basename($options['cacheFile']);
        }

        return Loader::fromOptions($options)->load($options, $routeDefinitionCallback);
    }
}
