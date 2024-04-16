<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;
use function Laravel\Prompts\table;
use function Laravel\Prompts\info;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missing:u:deploy {appName?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create stack
                             {appName : Optional. The name of the app}';


    private $baseUrl = 'http://missingu-app.test/api/v1';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * get username and password and get a token
         * zip up artifact
         * get the upload link from missingu
         * upload artifact to s3
         * kick off the deployment
         * Get the deployment details
         */
        if (!env('MISSINGU_API_TOKEN')) {
            $this->getMissingUToken();
        } else {
            info('Token already exists in .env file, using that.');
        }

        $appName = $this->argument('appName');

        if (!$appName) {
            $appName = text(
                label: 'Enter your applcation name',
                validate: ['name' => 'required|max:255|alpha_dash'],
                placeholder: "serverless-laravel-on-missingu"
            );
        }

        $url = $this->baseUrl . '/apps/' . $appName;

        $response = spin(
            fn () => Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . env('MISSINGU_API_TOKEN')
            ])->get(
                $url
            ),
            'Fetching app details...'
        );

        if ($response->status() !== 200) {
            $this->error('Failed to get app details');
            return;
        }

        $app = $response->json();

        table(
            ['Name', 'Region', 'Environment'],
            [
                [$app['name'], $app['region'], $app['environment']]
            ]
        );

        info('Creating artifact...');

        $artifact = $this->createArtifact();

        info('Getting Deployment');

        $url = $this->baseUrl . '/apps/' . $app['id'] . '/deployments';

        $response = spin(
            fn () => Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . env('MISSINGU_API_TOKEN')
            ])->post($url),
            'Fetching app details...'
        );

        if ($response->status() !== 200) {
            $response = spin(
                fn () => Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . env('MISSINGU_API_TOKEN')
                ])->get($url),
                'Getting all deployments'
            );

            if ($response->status() !== 200) {
                $this->error('Failed to get deployment details');
                return;
            }

            $deployments = $response->json();
            $deployment = $deployments['deployments'][0];
        } else {
            $deployment = $response->json();
        }

        table(
            ['ID', 'Status', 'Created At'],
            [
                [$deployment['id'], $deployment['status'], $deployment['created_at']]
            ]
        );

        info('Uploading Artifact to MissingU...');

        $response = spin(
            fn () => Http::attach(
                'artifact', file_get_contents(storage_path('app/artifact.zip')), 'artifact.zip', ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename=artifact.zip']
            )->put($deployment['upload_url']),
            'Uploading artifact...'
        );

        if ($response->status() !== 200) {
            $this->error('Failed to upload artifact');
            return;
        }

        info('Artifact uploaded successfully');

        $url = $this->baseUrl . '/deployments/' . $deployment['id'] . '/deploy';

        $response = spin(
            fn () => Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . env('MISSINGU_API_TOKEN')
            ])->post($url),
            'Starting deployment...'
        );

        if ($response->status() !== 200) {
            $this->error('Failed to start deployment');
            return;
        }

        info('Deployment started successfully');

        $id = $deployment['id'];

        info("run `missing:u:logs --deployment $id` to tail the deployment logs.");
        info("run `missing:u:destroy --app $appName` to destroy the stack.");
        info("run `missing:u:artisan --app $appName` --command '<command>' to run artisan commands.");
        info("Good luck out there.");
    }

    public function createArtifact()
    {
        $command = 'zip -r ' . storage_path('app/artifact.zip') . ' . -x "*node_modules*" -x "*vendor*" -x ".env" -x "*.cache" -x "*.git*" -x ".idea" -x ".DS_Store" -x "*storage/framework/*" -x "*storage/logs/*" -x "*public/build/*" -x "Dockerfile*" -x "*cdk/*"';
        Process::forever()
            ->idleTimeout(600)
            ->path(base_path())
            ->run($command, function ($type, $output) {
                $this->info($output);
            });
        info('Artifact created successfully and thrown into `storage/app/artifact.zip`');
    }

    public function getMissingUToken()
    {
        $username = text(
            label: 'Enter your MissingU username',
            validate: ['name' => 'required|max:255|email'],
            placeholder: "email@missingu.lol"
        );

        $password = password(
            label: 'Enter your MissingU password',
            validate: ['name' => 'required|max:255'],
            placeholder: "Shhhh...."
        );

        $response = spin(
            fn () => Http::withHeaders([
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/auth/create-token', [
                'email' => $username,
                'password' => $password
            ]),
            'Fetching access token...'
        );

        if ($response->status() !== 200) {
            $this->error('Failed to get token');
            return;
        }

        $token = $response->json()['token'];

        // dump token into the bottom of hte .env file
        file_put_contents(base_path('.env'), "\nMISSINGU_API_TOKEN=$token", FILE_APPEND);

        $this->info('Token saved to .env file');
    }
}
