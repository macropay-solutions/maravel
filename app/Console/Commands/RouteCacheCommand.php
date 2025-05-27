<?php

namespace App\Console\Commands;

use App\Router;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Laravel\Lumen\Application;

class RouteCacheCommand extends Command
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
    protected $name = 'route:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a route cache file for faster route registration';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new route command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('route:clear');
        $router = $this->getFreshApplicationRouter();

        $this->files->put(
            $this->laravel->getCachedRoutesPath(),
            '<?php return ' . \var_export($router->getCacheData(), true) . ';' . PHP_EOL
        );

        $this->info('Routes cached successfully.');
    }

    /**
     * Boot a fresh copy of the application and get the routes.
     */
    protected function getFreshApplicationRouter(): Router
    {
        /** @var Application $app */
        $app = require $this->laravel->bootstrapPath() . '/app.php';

        $app->useStoragePath($this->laravel->storagePath());

        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $app['router'];
    }
}
