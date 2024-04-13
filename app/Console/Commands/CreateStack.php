<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use App\Models\Stack;

class CreateStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missingu:create {appName} {appEnv} {--vpcId=vpc-05ce71fe4605909aa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create stack
                             {appName : The name of the app}
                             {appEnv : The environment of the app}
                             {--vpcId : The VPC ID of the app (defaults vpc-05ce71fe4605909aa)}';

    private $command;
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $outputsFile = storage_path('app/stack_outputs.json');
        $this->buildCommand(
            'speakcloud', 
            $this->argument('appName'), 
            $this->argument('appEnv'), 
            $this->option('vpcId'),
            $outputsFile
        );

        $this->info('Creating stacks...');
        $this->info($this->command);

        $cdkDirectory = base_path('cdk');

        Process::forever()
            ->idleTimeout(600)
            ->path($cdkDirectory)
            ->run($this->command, function ($type, $output) {
                $this->info($output);
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

        $this->info('Stack outputs retrieved successfully.');

        Stack::create([
            'user_id' => 1,
            'name' => $this->argument('appName'),
            'env' => $this->argument('appEnv'),
            'env_key' => $outputs['ServerlessLaravel']['envKey'],
            'bucket' => $outputs['ServerlessLaravel']['bucket'],
            'region' => $outputs['ServerlessLaravel']['region'],
            'account' => $outputs['ServerlessLaravel']['account']
        ]);
    }

    private function buildCommand($profile, $appName, $appEnv, $vpcId, $outputsFile)
    {
        $this->command = <<<DEPLOY
cdk deploy --profile $profile \
--require-approval never \
--outputs-file $outputsFile \
--context appName=$appName \
--context appEnv=$appEnv \
--context vpcId=$vpcId
DEPLOY;
    }
}
