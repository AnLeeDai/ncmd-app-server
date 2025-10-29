<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]).+$/'],
            'role' => ['in:user'],
        ]);

        $files = collect(Storage::disk('public')->files('avatars'))
            ->filter(fn($p) => Str::endsWith(Str::lower($p), ['.png', '.jpg', '.jpeg', '.gif', '.webp']))
            ->values()
            ->all();

        $avatarPath = !empty($files) ? $files[array_rand($files)] : 'avatars/AV1.png';
        $avatarUrl = url("/storage/$avatarPath");

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'avatar' => $avatarUrl,
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'User registered successfully', 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['sometimes', 'in:admin,user'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        if ($user->is_active == 0) {
            return $this->errorResponse('Account is inactive', 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid password', 401);
        }

        if ($user->role === 'admin') {
            if (!$request->has('role') || $request->role !== 'admin') {
                return $this->errorResponse('Cannot login', 403);
            }
        }

        Auth::login($user);

        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;

        $res = [
            'user' => $user,
            'token' => $token,
        ];

        return $this->successResponse($res, 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        if (!Auth::check()) {
            return $this->errorResponse('User not authenticated', 401);
        }

        return $this->successResponse(null, 'Logout successful');
    }
}
