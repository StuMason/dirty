<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestInternetAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stacks:internet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test hitting the fucking internet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Get google.com and dump out the source
            $google = Http::get("https://google.com");
            $this->info($google->getStatusCode());
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
        }
    }
}
