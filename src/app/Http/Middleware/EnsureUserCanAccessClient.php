<?php

namespace App\Http\Middleware;

use App\Models\AppClient;
use App\Services\ClientAccessChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class EnsureUserCanAccessClient
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected ClientAccessChecker $accessChecker
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authenticated user
        $user = auth()->user();

        // If no user is authenticated, deny access
        if (!$user) {
            abort(401, 'Usuario no autenticado.');
        }

        // Get the client_id from the current request's token
        $clientId = $user->token()?->client_id;

        // If no client_id is found, allow (might be a non-token request)
        if (!$clientId) {
            return $next($request);
        }

        // Load the client
        $client = AppClient::find($clientId);

        // If client doesn't exist, deny access
        if (!$client) {
            abort(403, 'Cliente de aplicación no encontrado.');
        }

        // Check if user can access this client
        if (!$this->accessChecker->userCanAccess($user, $client)) {
            Log::warning('Access denied for user ID ' . $user->id . ' to client ID ' . $client->id);
            abort(403, 'No tenés permiso para acceder a esta aplicación.');
        }

        return $next($request);
    }
}
