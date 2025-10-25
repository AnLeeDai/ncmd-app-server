<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        $userData = $user->toArray();
        $userData['total_points'] = $user->userPoints()->sum('points') ?? 0;

        return $this->successResponse($userData, 'User profile retrieved successfully');
    }

    public function users(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $users = User::where('role', '!=', 'admin')->paginate($perPage);

        if ($users->isEmpty()) {
            return $this->errorResponse('No users found', 404);
        }

        return $this->paginationResponse(
            $users,
            'Users retrieved successfully',
        );
    }

    public function getUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse($user, 'User retrieved successfully');
    }

    public function toggleUserActiveStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'unlocked' : 'locked';
        return $this->successResponse(null, "User account {$status} successfully");
    }
}