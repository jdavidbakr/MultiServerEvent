<?php

namespace jdavidbakr\MultiServerEvent\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MultiServerMigrationService extends Command {

	protected $signature = 'make:migration:multi-server-event';

	protected $description = 'Generate a migration for managing multi server events';

	protected $files;

	public function __construct()
	{
		$this->files = new Filesystem();
		return parent::__construct();
	}

	public function handle()
	{
		$migration_file = './database/migrations/' . date('Y_m_d_His') . '_multi-server-event.php';
		$this->files->put($migration_file, $this->compileMigrationStub());
	}

	protected function compileMigrationStub()
    {
        $stub = $this->files->get(__DIR__ . '/../stubs/migration.stub');

        return $stub;
    }
}
?>