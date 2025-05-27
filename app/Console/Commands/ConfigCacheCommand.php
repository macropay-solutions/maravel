<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Laravel\Lumen\Application;
use LogicException;
use Throwable;

class ConfigCacheCommand extends Command
{
    /**
     * The Lumen application instance.
     *
     * @var \App\Application
     */
    protected $laravel;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'config:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache file for faster configuration loading';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new config cache command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @throws \LogicException
     */
    public function handle(): void
    {
        $this->call('config:clear');

        $config = $this->getFreshConfiguration();
        $configPath = $this->laravel->getCachedConfigPath();

        $this->files->put($configPath, '<?php return ' . \var_export($config, true) . ';' . PHP_EOL);

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->info('Configuration cached successfully!');
    }

    /**
     * Boot a fresh copy of the application configuration.
     */
    protected function getFreshConfiguration(): array
    {
        /** @var Application $app */
        $app = require $this->laravel->bootstrapPath() . '/app.php';

        $app->useStoragePath($this->laravel->storagePath());

        $app->make(ConsoleKernelContract::class)->bootstrap();
        $app->boot();

        foreach ($app->availableBindings as $binding => $resolver) {
            try {
                $app->make($binding);
            } catch (\Throwable $e) {
                $this->info($e->getMessage());
            }
        }

        return $app['config']->all();
    }
}
