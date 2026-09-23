<?php

declare(strict_types=1);

namespace StaffHub\Core;

/**
 * Front-controller application: match route → middleware → controller → send.
 */
final class Application
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
    ) {
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        $match = $this->router->match($request->method(), $request->path());

        if ($match === null) {
            Response::notFound('The page you requested does not exist.')->send();
            return;
        }

        $request = $request->withRouteParams($match['params']);

        foreach ($match['middleware'] as $middleware) {
            $response = $middleware($request);
            if ($response instanceof Response) {
                $response->send();
                return;
            }
        }

        [$class, $method] = $match['handler'];

        try {
            $controller = $this->container->get($class);
            $response = $controller->{$method}($request);

            if (!$response instanceof Response) {
                throw new \RuntimeException(
                    "Controller [{$class}::{$method}] must return a " . Response::class . '.'
                );
            }
        } catch (\Throwable $e) {
            error_log('Unhandled application error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $response = Response::html(
                '<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;">500 — Something went wrong</h2>',
                500
            );
        }

        $response->send();
    }
}
