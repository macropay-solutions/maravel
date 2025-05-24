<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ConfigClearCommand extends Command
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
    protected $name = 'config:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the configuration cache file';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new config clear command instance.
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
        $this->files->delete($this->laravel->getCachedConfigPath());

        $this->info('Configuration cache cleared!');
    }
}
