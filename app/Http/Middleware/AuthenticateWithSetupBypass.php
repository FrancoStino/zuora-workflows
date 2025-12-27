<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithSetupBypass extends FilamentAuthenticate
{
    /**
     * Handle an incoming request with setup check and authentication bypass.
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $isSetupRoute = $this->isSetupRoute($request);
        $setupCompleted = $this->isSetupCompleted();

        // Se il setup non è completato
        if (!$setupCompleted) {
            // Permetti accesso alla pagina setup senza autenticazione
            if ($isSetupRoute) {
                return $next($request);
            }

            // Reindirizza tutte le altre richieste al setup
            return redirect('/setup');
        }

        // Setup completato + accesso a /setup (senza parametro reset)
        if ($isSetupRoute && !$request->has('reset')) {
            // Reindirizza basato sullo status di autenticazione
            return Auth::check() ? redirect('/') : redirect('/login');
        }

        // Setup completato: applica la normale autenticazione di Filament
        return parent::handle($request, $next, ...$guards);
    }

    /**
     * Determine if the request is for the setup page.
     */
    private function isSetupRoute($request): bool
    {
        return $request->is('setup') || $request->is('setup/*');
    }

    /**
     * Determine if setup is completed.
     */
    private function isSetupCompleted(): bool
    {
        try {
            if (!Schema::hasTable('setup_completed')) {
                return false;
            }

            $setupCompleted = DB::table('setup_completed')->first();

            return $setupCompleted && $setupCompleted->completed;
        } catch (Exception) {
            return false;
        }
    }
}
