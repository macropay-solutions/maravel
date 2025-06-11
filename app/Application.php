<?php

namespace App;

use FastRoute\Dispatcher;
use Illuminate\Config\Repository;
use Laravel\Lumen\Bootstrap\LoadEnvironmentVariables;

class Application extends \Laravel\Lumen\Application
{
    /**
     * @inheritdoc
     */
    public $availableBindings = [
        'auth' => 'registerAuthBindings',
        'auth.driver' => 'registerAuthBindings',
        \Illuminate\Auth\AuthManager::class => 'registerAuthBindings',
        \Illuminate\Contracts\Auth\Guard::class => 'registerAuthBindings',
        \Illuminate\Contracts\Auth\Access\Gate::class => 'registerAuthBindings',
        \Illuminate\Contracts\Broadcasting\Broadcaster::class => 'registerBroadcastingBindings',
        \Illuminate\Contracts\Broadcasting\Factory::class => 'registerBroadcastingBindings',
        \Illuminate\Contracts\Bus\Dispatcher::class => 'registerBusBindings',
        'cache' => 'registerCacheBindings',
        'cache.store' => 'registerCacheBindings',
        \Illuminate\Contracts\Cache\Factory::class => 'registerCacheBindings',
        \Illuminate\Contracts\Cache\Repository::class => 'registerCacheBindings',
        'composer' => 'registerComposerBindings',
        'config' => 'registerConfigBindings',
        'db' => 'registerDatabaseBindings',
        /** Illuminate\Database\Eloquent\Factory::class => 'registerDatabaseBindings', */ // removed since V8
        'filesystem' => 'registerFilesystemBindings',
        'filesystem.cloud' => 'registerFilesystemBindings',
        'filesystem.disk' => 'registerFilesystemBindings',
        \Illuminate\Contracts\Filesystem\Cloud::class => 'registerFilesystemBindings',
        \Illuminate\Contracts\Filesystem\Filesystem::class => 'registerFilesystemBindings',
        \Illuminate\Contracts\Filesystem\Factory::class => 'registerFilesystemBindings',
        'encrypter' => 'registerEncrypterBindings',
        \Illuminate\Contracts\Encryption\Encrypter::class => 'registerEncrypterBindings',
        'events' => 'registerEventBindings',
        \Illuminate\Contracts\Events\Dispatcher::class => 'registerEventBindings',
        'files' => 'registerFilesBindings',
        'hash' => 'registerHashBindings',
        \Illuminate\Contracts\Hashing\Hasher::class => 'registerHashBindings',
        'log' => 'registerLogBindings',
        \Psr\Log\LoggerInterface::class => 'registerLogBindings',
        'queue' => 'registerQueueBindings',
        'queue.connection' => 'registerQueueBindings',
        \Illuminate\Contracts\Queue\Factory::class => 'registerQueueBindings',
        \Illuminate\Contracts\Queue\Queue::class => 'registerQueueBindings',
        'router' => 'registerRouterBindings',
        \Psr\Http\Message\ServerRequestInterface::class => 'registerPsrRequestBindings',
        \Psr\Http\Message\ResponseInterface::class => 'registerPsrResponseBindings',
        'translator' => 'registerTranslationBindings',
        'url' => 'registerUrlGeneratorBindings',
        'validator' => 'registerValidatorBindings',
        \Illuminate\Contracts\Validation\Factory::class => 'registerValidatorBindings',
        'view' => 'registerViewBindings',
        \Illuminate\Contracts\View\Factory::class => 'registerViewBindings',
    ];

    /**
     * The prefixes of absolute cache paths for use during normalization.
     */
    protected array $absoluteCachePathPrefixes = ['/', '\\'];

    public function __construct($basePath = null)
    {
        if (!$this->configurationIsCached()) {
            (new LoadEnvironmentVariables(\dirname(__DIR__)))->bootstrap();
            \date_default_timezone_set(\env('APP_TIMEZONE', 'UTC'));

            parent::__construct($basePath);

            return;
        }

        parent::__construct($basePath);

        \date_default_timezone_set(\config('app.timezone', 'UTC'));
    }

    /**
     * Determine if the application configuration is cached.
     */
    public function configurationIsCached(): bool
    {
        return \is_file($this->getCachedConfigPath());
    }

    /**
     * Get the path to the configuration cache file.
     */
    public function getCachedConfigPath(): string
    {
        return $this->bootstrapPath('cache' . DIRECTORY_SEPARATOR . 'config.php');
    }

    /**
     * Get the path to the bootstrap directory.
     */
    public function bootstrapPath(string $path = ''): string
    {
        return $this->basePath(\rtrim(
            DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $path,
            DIRECTORY_SEPARATOR
        ));
    }

    /**
     * @inheritDoc
     */
    public function configure($name): void
    {
        if (isset($this->loadedConfigurations[$name])) {
            return;
        }

        $this->loadedConfigurations[$name] = true;

        if ($this->configurationIsCached()) {
            return;
        }

        if ('' !== $path = $this->getConfigurationPath($name)) {
            $this->make('config')->set($name, require $path);
        }
    }

    /**
     * Get the path to the routes cache file.
     */
    public function getCachedRoutesPath(): string
    {
        return $this->bootstrapPath('cache' . DIRECTORY_SEPARATOR . 'routes-v7.php');
    }

    /**
     * Get the path to the fast routes cache file.
     */
    public function getCachedFastRoutesPath(): string
    {
        return $this->bootstrapPath('cache' . DIRECTORY_SEPARATOR . 'fast_routes.php');
    }

    /**
     * @inheritDoc
     */
    public function bootstrapRouter(): void
    {
        $this->router = new Router($this);
    }

    /**
     * Determine if the application routes are cached.
     */
    public function routesAreCached(): bool
    {
        return \is_file($this->getCachedRoutesPath());
    }

    /**
     * @inheritDoc
     */
    protected function registerConfigBindings(): void
    {
        $this->singleton('config', function (\App\Application $app): Repository {
            if (\file_exists($cached = $app->getCachedConfigPath())) {
                return new Repository(require $cached);
            }

            return new Repository();
        });
    }

    /**
     * @inheritdoc
     */
    protected function createDispatcher(): Dispatcher
    {
        if (isset($this->dispatcher)) {
            return $this->dispatcher;
        }

        $closure = function (\FastRoute\RouteCollector $r): void {
            foreach ($this->router->getRoutes() as $route) {
                $r->addRoute($route['method'], $route['uri'], $route['action']);
            }
        };

        if (!$this->routesAreCached()) {
            return \FastRoute\simpleDispatcher($closure);
        }

        return \FastRoute\cachedDispatcher($closure, [
            'cacheFile' => $this->getCachedFastRoutesPath()
        ]);
    }
}
