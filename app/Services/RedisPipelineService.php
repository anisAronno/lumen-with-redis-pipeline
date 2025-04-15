<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class RedisPipelineService
{
    /**
     * Execute the Redis pipeline logic for a given user.
     *
     * @param int $userId
     * @param string $userEmail
     * @return void
     */
    public function handleUserPipeline(int $userId, string $userEmail): void
    {
        $today = Carbon::now()->format('Ymd');

        Redis::pipeline(function ($pipe) use ($userId, $userEmail, $today) {

            // 1️⃣ String – Save JWT token
            $pipe->set("user:$userId:token", 'eyJhbGciOi...');

            // 2️⃣ Hash – Cache user profile
            $pipe->hmset("user:$userId:profile", [
                'email' => $userEmail,
                'name' => 'Anis',
                'role' => 'customer',
            ]);

            // 3️⃣ List – Push to notification queue
            $pipe->rpush('queue:notifications', json_encode([
                'to' => $userEmail,
                'subject' => 'Welcome!',
                'message' => 'Thanks for joining us.'
            ]));

            // 4️⃣ List – Add transaction to recent list
            $pipe->lpush("user:$userId:transactions", json_encode([
                'id' => uniqid(),
                'amount' => 100,
                'timestamp' => Carbon::now()->timestamp,
            ]));

            // Limit to 10 items
            $pipe->ltrim("user:$userId:transactions", 0, 9);

            // 5️⃣ HyperLogLog – Track unique logins
            $pipe->pfadd("unique:logins:$today", [$userId]);

            // 6️⃣ Bitmap – Track login presence
            $pipe->setbit("login:$today", $userId, 1);

            // 7️⃣ Sorted Set – Add score to leaderboard
            $pipe->zadd("referral:leaderboard", 50, $userId);

            // 8️⃣ Set – Tag user as "first_deposit"
            $pipe->sadd("user:$userId:tags", 'first_deposit');

            // 9️⃣ Stream – Log transaction to event stream
            $pipe->xadd("stream:transactions", '*', [
                'user_id' => $userId,
                'amount' => 100,
                'type' => 'deposit',
                'status' => 'success'
            ]);

            // 🔟 Geo – Set user location
            $pipe->geoadd("geo:users", 90.4125, 23.8103, $userId); // Dhaka coords
        });
    }
}
