<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production
        if (app()->environment('production')) {
            // Check if request is already HTTPS (including from proxy)
            $isSecure = $request->secure() || 
                        $request->header('X-Forwarded-Proto') === 'https' ||
                        $request->header('X-Forwarded-Ssl') === 'on';
            
            // Only redirect if not secure
            if (!$isSecure) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
