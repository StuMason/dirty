<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Stack;
use Illuminate\Support\Facades\Log;

class CreateStackTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Getting all stacks...');
            Log::info(Stack::all(['id', 'name', 'env', 'env_key', 'bucket', 'region', 'account'])->toArray());
            Stack::create([
                'name' => 'Test Stack',
                'env' => 'test',
                'env_key' => 'test',
                'bucket' => 'test',
                'region' => 'us-east-1',
                'account' => 'test'
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw $th;
        }
    }
}
