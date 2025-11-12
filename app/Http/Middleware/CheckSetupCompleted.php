<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for setup route itself
        if ($request->is('setup') || $request->is('setup/*')) {
            return $next($request);
        }

        try {
            // Check if setup_completed table exists
            if (! Schema::hasTable('setup_completed')) {
                return $next($request);
            }

            // Check if setup is completed
            $setupCompleted = DB::table('setup_completed')->first();

            if (! $setupCompleted || ! $setupCompleted->completed) {
                return redirect('/setup');
            }
        } catch (\Exception $e) {
            // If there's any error, allow the request to continue
            // This prevents the app from breaking during migrations or setup
            return $next($request);
        }

        return $next($request);
    }
}
