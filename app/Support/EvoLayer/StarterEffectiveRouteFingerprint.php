<?php

namespace App\Support\EvoLayer;

use Illuminate\Routing\Router;
use JsonException;

final readonly class StarterEffectiveRouteFingerprint
{
    public function __construct(private Router $router) {}

    /** @return list<array<string, mixed>> */
    public function routes(): array
    {
        return collect($this->router->getRoutes()->getRoutes())
            ->map(function ($route): array {
                $methods = $route->methods();
                $middleware = array_map(
                    fn (mixed $item): string => is_string($item) ? $item : get_debug_type($item),
                    $route->gatherMiddleware(),
                );
                sort($methods);
                sort($middleware);

                return [
                    'name' => $route->getName() ?? '',
                    'domain' => $route->getDomain() ?? '',
                    'uri' => $route->uri(),
                    'methods' => $methods,
                    'action' => $route->getActionName(),
                    'middleware' => $middleware,
                ];
            })
            ->sortBy(fn (array $route): string => implode('|', [
                $route['domain'],
                $route['uri'],
                implode(',', $route['methods']),
                $route['name'],
                $route['action'],
            ]))
            ->values()
            ->all();
    }

    public function hash(): string
    {
        try {
            return hash('sha256', json_encode($this->routes(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            return '';
        }
    }
}
