<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisCacheService
{
    protected string $tag = 'users';
    protected int $ttl = 600; // 10 minutes

    public function cacheUsers(array $users): void
    {
        Redis::pipeline(function ($pipe) use ($users) {
            foreach ($users as $user) {
                $key = "user:{$user['id']}";
                $pipe->setex($key, $this->ttl, json_encode($user));
                $pipe->sadd("tag:{$this->tag}", $key); // Track keys by tag
            }
        });
    }

    public function getUsers(array $ids): array
    {
        return Redis::pipeline(function ($pipe) use ($ids) {
            foreach ($ids as $id) {
                $pipe->get("user:$id");
            }
        });
    }

    public function deleteUserFromCache(int $id): void
    {
        $key = "user:$id";
        Redis::pipeline(function ($pipe) use ($key) {
            $pipe->del($key);
            $pipe->srem("tag:{$this->tag}", $key);
        });
    }

    public function clearAllUserCache(): void
    {
        $keys = Redis::smembers("tag:{$this->tag}");
        if (!empty($keys)) {
            Redis::pipeline(function ($pipe) use ($keys) {
                foreach ($keys as $key) {
                    $pipe->del($key);
                    $pipe->srem("tag:users", $key);
                }
            });
        }
    }
}
