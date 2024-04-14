<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Stack;

class TestArtisan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stacks:artisan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test getting all stacks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->table(
            ['ID', 'Name', 'Env', 'Env Key', 'Bucket', 'Region', 'Account'],
            Stack::all(['id', 'name', 'env', 'env_key', 'bucket', 'region', 'account'])->toArray()
        );
    }
}
