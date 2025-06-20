<?php

namespace App\Console\Commands;

use App\Router;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

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
        /** @var Router $router */
        $router = \app('router');

        $this->files->put(
            $path = $this->laravel->getCachedRoutesPath(),
            '<?php return ' . \var_export($router->getCacheData(), true) . ';' . PHP_EOL
        );

        try {
            require $path;
        } catch (\Throwable $e) {
            $this->files->delete($path);

            throw new \LogicException('Your routes are not serializable.', 0, $e);
        }

        $this->info('Routes cached successfully.');
    }
}
