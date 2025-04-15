<?php

namespace App\Jobs;

use App\Services\RedisCacheService;

class CacheUsersJob extends Job
{
    protected array $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function handle(RedisCacheService $cacheService): void
    {
        $cacheService->cacheUsers($this->users);
    }
}

