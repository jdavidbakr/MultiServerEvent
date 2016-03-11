<?php

namespace jdavidbakr\MultiServerEvent\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MultiServerMigrationService extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'make:migration:multi-server-event';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate a migration for managing multi server events';

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Generate a migration for managing multi server events.
     */
    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $migration_file = './database/migrations/'.date('Y_m_d_His').'_multi-server-event.php';
        $this->files->put($migration_file, $this->compileMigrationStub());
    }

    /**
     * Get the migration stub.
     * @return string
     */
    protected function compileMigrationStub()
    {
        $stub = $this->files->get(__DIR__.'/../stubs/migration.stub');

        return $stub;
    }
}
