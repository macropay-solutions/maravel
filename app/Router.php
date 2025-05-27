<?php

namespace App;

class Router extends \Laravel\Lumen\Routing\Router
{
    public function __construct(Application $app)
    {
        if (\file_exists($cached = $app->getCachedRoutesPath())) {
            $router = require $cached;
            $this->groupStack = $router['groupStack'];
            $this->routes = $router['routes'];
            $this->namedRoutes = $router['namedRoutes'];
        }

        parent::__construct($app);
    }

    public function getCacheData(): array
    {
        return [
            'groupStack' => $this->groupStack,
            'routes' => $this->routes,
            'namedRoutes' => $this->namedRoutes,
        ];
    }
}
