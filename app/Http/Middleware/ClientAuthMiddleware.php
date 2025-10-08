<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Client Authentication Middleware
 * 
 * Handles authentication for client applications using Laravel Sanctum.
 * Validates API tokens, public keys, and origin domains to ensure secure access.
 * All API requests must include proper Authorization and X-Public-Key headers.
 * 
 * @package App\Http\Middleware
 * @author Laravel Slime Talks
 * @version 1.0.0
 * 
 * @example
 * // Required headers for API requests:
 * Authorization: Bearer your_api_token_here
 * X-Public-Key: pk_your_public_key_here
 * Origin: yourdomain.com
 */
class ClientAuthMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Validates the request by checking for required headers and authenticating
     * the client using Sanctum tokens and public key verification.
     * Also validates the origin domain against the client's allowed domain.
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The response from the next middleware or an error response
     * 
     * @throws \Illuminate\Http\Exceptions\HttpResponseException When authentication fails
     * 
     * @example
     * // This middleware will be applied to protected routes
     * Route::middleware(['client.auth'])->group(function () {
     *     Route::get('/customers', [CustomerController::class, 'index']);
     * });
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for Authorization header
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            Log::warning('Authentication failed - Missing or invalid Authorization header', [
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'auth_header_present' => !empty($authHeader),
                'auth_header_format' => $authHeader ? 'invalid' : 'missing',
            ]);
            return response()->json(['error' => 'Unauthorized - Missing or invalid Authorization header'], 401);
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

        // Check for Public Key header
        $publicKey = $request->header('X-Public-Key');
        if (!$publicKey) {
            Log::warning('Authentication failed - Missing X-Public-Key header', [
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized - Missing X-Public-Key header'], 401);
        }

        // Find client by public key
        $client = Client::where('public_key', $publicKey)->first();
        if (!$client) {
            Log::warning('Authentication failed - Invalid public key', [
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'public_key' => $publicKey,
            ]);
            return response()->json(['error' => 'Unauthorized - Invalid public key'], 401);
        }

        // Use Sanctum's built-in token verification
        $tokenRecord = PersonalAccessToken::findToken($token);
        if (!$tokenRecord || $tokenRecord->tokenable_id !== $client->id) {
            Log::warning('Authentication failed - Invalid token for client', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized - Invalid token for this client'], 401);
        }

        // Check if token is expired
        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            Log::warning('Authentication failed - Token expired', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'token_expires_at' => $tokenRecord->expires_at,
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized - Token expired'], 401);
        }

        // Set the authenticated client using Sanctum's proper authentication
        Auth::guard('sanctum')->setUser($client);
        $request->setUserResolver(function () use ($client) {
            return $client;
        });

        // Update last used timestamp for the token
        $tokenRecord->update(['last_used_at' => now()]);

        return $next($request);
    }


}
