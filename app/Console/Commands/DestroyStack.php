<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class DestroyStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missingu:destroy {appName} {appEnv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'destroy stack
                             {appName : The name of the app}
                             {appEnv : The environment of the app}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Destroying stack...');
        $profile = 'speakcloud';
        $appName = $this->argument('appName');
        $appEnv = $this->argument('appEnv');
        $stackCommand = "cdk destroy --profile $profile --force --context appName=$appName --context appEnv=$appEnv";
        Process::forever()
            ->idleTimeout(120)
            ->path('./cdk')
            ->run($stackCommand, function ($type, $output) {
                $this->info($output);
            });
        $this->info('Stack Destroyed successfully.');
    }
}
