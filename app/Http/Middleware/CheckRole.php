<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole extends Controller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        $hasRole = match ($role) {
            'admin' => $user->isAdmin(),
            'user' => $user->isUser(),
            default => false,
        };

        if (!$hasRole) {
            return $this->errorResponse('Forbidden', 403);
        }

        return $next($request);
    }
}
