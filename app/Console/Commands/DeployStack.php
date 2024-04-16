<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missingu:deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the post deploy commands.';

    private $appName;
    private $appEnv;
    private $mountPoint;

    public function __construct()
    {
        parent::__construct();
        $this->appName = env('APP_NAME');
        $this->appEnv = env('APP_ENV');
        $this->mountPoint = "/mnt/store/";
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment('local')) {
            $this->error('This command can only be run in the serverless environment.');
            return;
        }

        if (!file_exists($this->mountPoint)) {
            $this->error('Storage mount point not found.');
            return;
        }

        if (!file_exists("$this->mountPoint/deployed")) {
            $this->info('Running post deploy commands for the first time...');
            touch("$this->mountPoint/deployed");
        }

        $this->info('Running post deploy commands...');
        // $this->decryptEnv();
        $this->databaseSetup();

    }

    private function databaseSetup()
    {
        try {
            $this->info('Checking database...');
            if (file_exists("$this->mountPoint/database.sqlite")) {
                $this->info('Database already exists.');
            } else {
                file_put_contents("$this->mountPoint/database.sqlite", '');
            }
            $this->info('Migrating database...');
            $this->call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }
    }
}
