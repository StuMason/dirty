<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class CreateStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missingu:create';

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
        $this->info('Deploying stack...');
        $outputsFile = storage_path('app/stack_outputs.json');
        $profile = 'speakcloud';
        $stackCommand = <<<DEPLOY
        cdk deploy --profile $profile \
        --require-approval never --outputs-file $outputsFile \
        --context stackName=ServerlessLaravel \
        --parameters appName=serverless-laravel \
        --parameters appEnv=dev
DEPLOY;
        $this->count = 0;
        Process::forever()
            ->idleTimeout(120)
            ->path('./cdk')
            ->run($stackCommand, function ($type, $output) {
                $this->info($output);
                $this->count++;
                $this->error('Count: ' . $this->count);
            });
        $this->info('Stack deployed successfully. Getting outputs...');
        $outputs = json_decode(file_get_contents($outputsFile), true);
        $this->table(
            ['Type', 'Resource Name'],
            collect($outputs['ServerlessLaravel'])->map(function ($value, $key) {
                return [
                    "Type" => $key,
                    "Resource Name" => $value
                ];
            })
        );
    }
}
