<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate();

        return UserResource::collection($users)->additional([
            'message' => 'Users retrieved successfully',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return UserResource::make($user)->additional([
            'message' => 'User retrieved successfully',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create($request->only('name', 'email', 'password'));

        return UserResource::make($user)->additional([
            'message' => 'User created successfully',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|confirmed|min:6'
        ]);

        $user->update($request->only('name', 'email', 'password'));
        
        return UserResource::make($user)->additional([
            'message' => 'User updated successfully',
            'status'  => 'success',
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if($user->delete()) {
            return response()->json([
                'message' => 'User deleted successfully',
                'status'  => 'success',
            ], Response::HTTP_OK);
        }
        
        return response()->json([
            'message' => 'User deletion failed',
            'status'  => 'error',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

}
