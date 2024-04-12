<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MissingUDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missingu:deploy {key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the post deploy commands.
                              {key : The key to decrypt the env file}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!file_exists('/mnt/store/deployed')) {
            $this->info('Running post deploy commands for the first time...');
        } else {
            $this->info('Running post deploy commands...');
        }
        $this->key = $this->argument('key');
        $this->decryptEnv();
        $this->databaseSetup();

    }

    private function databaseSetup()
    {
        try {
            $this->info('Checking database...');
            if (file_exists('/mnt/store/database.sqlite')) {
                $this->info('Database already exists.');
            } else {
                file_put_contents('/mnt/store/database.sqlite', '');
            }
            $this->info('Migrating database...');
            $this->call('migrate', ['--force' => true]);
            $this->info('Database created successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }
    }

    private function decryptEnv()
    {
        try {
            $this->call('env:decrypt', ['--key' => $this->key, '--env' => 'serverless', '--force' => true]);
            rename('/var/task/.env.serverless', '/mnt/store/.env');
            $this->info('Env file decrypted successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to decrypt the env file...');
            $this->error($e->getMessage());
            return;
        }
    }
}
