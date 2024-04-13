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
    protected $signature = 'missingu:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run CDK commands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Destroying stack...');
        // PROBABLY SHOULD GET A COPY OF THE DB HERE SOMEHOW?
        $profile = 'speakcloud';
        $stackName = 'ServerlessLaravel';
        $stackCommand = "cdk destroy --profile $profile --force --context stackName=$stackName";
        $this->count = 0;
        Process::forever()
            ->idleTimeout(120)
            ->path('./cdk')
            ->run($stackCommand, function ($type, $output) {
                $this->info($output);
                $this->count++;
                $this->error('Count: ' . $this->count);
            });
        $this->info('Stack Destroyed successfully.');
        $outputs = json_decode(file_get_contents($outputsFile), true);
    }
}
