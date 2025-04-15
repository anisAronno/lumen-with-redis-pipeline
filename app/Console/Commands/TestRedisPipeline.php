<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisPipelineService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class TestRedisPipeline extends Command
{
    protected $signature = 'test:redis-pipeline';
    protected $description = 'Test Redis pipeline functionality';

    protected RedisPipelineService $pipelineService;

    public function __construct(RedisPipelineService $pipelineService)
    {
        parent::__construct();
        $this->pipelineService = $pipelineService;
    }

    public function handle()
    {
        $userId = 123;
        $userEmail = 'user@example.com';

        $this->pipelineService->handleUserPipeline($userId, $userEmail);

        $this->info('✅ Redis pipeline executed successfully.');
        $this->line('📦 Displaying Redis data snapshot:');

        // Output data snapshot
        $this->info("Token: " . Redis::get("user:$userId:token"));
        $this->info("Profile: ");
        $this->line(print_r(Redis::hgetall("user:$userId:profile"), true));

        $this->info("Recent Transactions: ");
        $this->line(print_r(Redis::lrange("user:$userId:transactions", 0, 9), true));

        $this->info("Login Bitmap Today: " . Redis::getbit("login:" . Carbon::now()->format('Ymd'), $userId));

        $this->info("Leaderboard Score: " . Redis::zscore("referral:leaderboard", $userId));
        $this->info("User Tags: ");
        $this->line(print_r(Redis::smembers("user:$userId:tags"), true));

        $this->info("Nearby Users (Geo): ");
        $this->line(print_r(Redis::geopos("geo:users", $userId), true));
    }
}
