<?php

namespace App;

use Illuminate\Config\Repository;
use Illuminate\Support\Str;
use Laravel\Lumen\Bootstrap\LoadEnvironmentVariables;

class Application extends \Laravel\Lumen\Application
{
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
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
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
     * Normalize a relative or absolute path to a cache file.
     */
    protected function normalizeCachePath(string $key, string $default): string
    {
        if (\is_null($env = \env($key))) {
            return $this->bootstrapPath($default);
        }

        return Str::startsWith($env, $this->absoluteCachePathPrefixes) ? $env : $this->basePath($env);
    }
}
