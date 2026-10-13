<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_suspended && $user->getRoleNames()->isNotEmpty()) {
            return $next($request);
        }

        $message=!$user?__('Sign in to continue'):($user->is_suspended?__('Your account is temporarily suspended'):__('Your account is not authorized'));
        if ($user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if($request->expectsJson())return response()->json(['message'=>$message],$user?403:401);
        return redirect()->route('login')->with($user?'error':'warning', $message);
    }
}
