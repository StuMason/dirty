<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class MissinguDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missing:u:deploy {appName} {appEnv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy your stack to missing:u';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * Zip up the codebase and send it to missing:u
         * 
         * User needs to login first if they haven't already
         * we create the zip file of the codebase
         * We then hit missing:u for a signedURL to upload the zip
         * We then upload the zip to the signedURL
         */
        $this->authenticate();
        $this->zipCodebase();
        $this->getSignedUrl();
        $this->uploadZip();
    }

    private function authenticate()
    {
        $this->info('Authenticating with missing:u...');
    }

    private function zipCodebase()
    {
        $this->info('Creating the missing:u artifact...');
        // Zip up the codebase
        $dirty = null;

        $run = Process::run('git status --porcelain');
        dd($run->output());

        dd($dirty);
    
        $command = "git archive -o missingu_artifact.zip -9 HEAD";
        $this->process = Process::timeout(240)->run($command);
    }

    private function getSignedUrl()
    {
        $this->info('Getting signed URL from missing:u...');
    }

    private function uploadZip()
    {
        $this->info('Uploading zip to missing:u...');
    }
}
