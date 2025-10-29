<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CookieController extends Controller
{
    /**
     * Set a cross-site cookie for testing from the frontend.
     * Returns JSON and attaches a cookie named `ncmd_test` with SameSite=None; Secure; HttpOnly.
     */
    public function setTestCookie(Request $request)
    {
        $value = $request->input('value', 'hello-from-server');

        $minutes = 60; // 1 hour

        $cookie = cookie('ncmd_test', $value, $minutes, '/', config('session.domain'), config('session.secure'), true, false, config('session.same_site'));

        return response()->json(['success' => true, 'cookie' => 'ncmd_test'])->withCookie($cookie);
    }
}

