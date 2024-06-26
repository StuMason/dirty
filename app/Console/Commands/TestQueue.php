<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CreateStackTest;

class TestQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stacks:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test queueing a job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CreateStackTest::dispatch();
    }
}
