<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class RouteClearCommand extends Command
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
    protected $name = 'route:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the route cache file';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new route clear command instance.
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
        $this->files->delete($this->laravel->getCachedRoutesPath());
        $this->files->delete($this->laravel->getCachedFastRoutesPath());

        $this->info('Route cache cleared successfully.');
    }
}
