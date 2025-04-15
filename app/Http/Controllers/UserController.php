<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\RedisCacheService;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use App\Jobs\CacheUsersJob;

class UserController extends Controller
{
    protected RedisCacheService $cacheService;

    public function __construct(RedisCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(): JsonResponse
    {
        $ids = User::pluck('id')->toArray();
        $cached = $this->cacheService->getUsers($ids);

        $users = [];
        $missed = [];

        foreach ($cached as $index => $data) {
            if ($data) {
                $users[] = json_decode($data, true);
            } else {
                $missed[] = $ids[$index];
            }
        }

        if (!empty($missed)) {
            $freshUsers = User::whereIn('id', $missed)->orderByDesc('created_at')->get()->toArray();
            $users = array_merge($users, $freshUsers);
            dispatch(new CacheUsersJob($freshUsers)); // Async sync
        }


        $users = collect($users)->map(fn($user) => (object) $user);

        return UserResource::collection(collect($users))->additional([
            'message' => 'Users loaded with cache fallback',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function show($id): JsonResponse
    {
        $cached = $this->cacheService->getUsers([$id])[0] ?? null;

        if ($cached) {
            $user = json_decode($cached, true);
        } else {
            $user = User::findOrFail($id)->toArray();
            dispatch(new CacheUsersJob([$user]));
        }

        return UserResource::make((object)$user)->additional([
            'message' => 'User loaded with cache fallback',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create($validated);
        dispatch(new CacheUsersJob([$user->toArray()]));

        return UserResource::make($user)->additional([
            'message' => 'User created and synced to Redis',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $this->validate($request, [
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|confirmed|min:6'
        ]);

        $user->update($validated);
        dispatch(new CacheUsersJob([$user->toArray()]));

        return UserResource::make($user)->additional([
            'message' => 'User updated and Redis cache refreshed',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->delete()) {
            $this->cacheService->deleteUserFromCache($id);

            return response()->json([
                'message' => 'User deleted and cache cleared',
                'status'  => 'success',
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'User deletion failed',
            'status'  => 'error',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
