<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        \Log::info('CheckPermission middleware called for permission: ' . $permission);
        \Log::info('User authenticated: ' . (Auth::check() ? 'Yes' : 'No'));
        
        if (!Auth::check()) {
            \Log::warning('User not authenticated, redirecting to login');
            return redirect('/login');
        }

        $user = Auth::user();
        \Log::info('User ID: ' . $user->id . ', Role: ' . ($user->role ? $user->role->name : 'No role'));
        
        if (!$user->hasPermission($permission)) {
            \Log::warning('User does not have permission: ' . $permission);
            abort(403, 'Unauthorized action.');
        }

        \Log::info('Permission check passed for: ' . $permission);
        return $next($request);
    }
}
