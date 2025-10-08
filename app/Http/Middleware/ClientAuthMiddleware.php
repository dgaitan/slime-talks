<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Verify the token belongs to this client using Sanctum's built-in verification
        $tokenRecord = $client->tokens()->where('name', 'test-token')->first();
        if (!$tokenRecord) {
            Log::warning('Authentication failed - Invalid token for client', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized - Invalid token for this client'], 401);
        }

        // Check origin domain
        $origin = $request->header('Origin') ?? $request->header('Host');
        if ($origin) {
            $allowedDomain = $client->domain;
            
            // Check if the origin matches the client's domain or is a subdomain
            if (!$this->isValidOrigin($origin, $allowedDomain)) {
                Log::warning('Authentication failed - Invalid origin domain', [
                    'client_id' => $client->id,
                    'client_uuid' => $client->uuid,
                    'origin' => $origin,
                    'allowed_domain' => $allowedDomain,
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Unauthorized - Invalid origin domain'], 401);
            }
        }

        // Set the authenticated client
        Auth::guard('sanctum')->setUser($client);
        $request->setUserResolver(function () use ($client) {
            return $client;
        });

        return $next($request);
    }

    /**
     * Check if the origin is valid for the client's domain.
     * 
     * Validates that the request origin matches the client's allowed domain.
     * Supports both exact domain matches and subdomain matches for flexibility.
     * Removes protocol and port information before comparison.
     * 
     * @param string $origin The request origin (from Origin or Host header)
     * @param string $allowedDomain The client's allowed domain
     * @return bool True if origin is valid, false otherwise
     * 
     * @example
     * $isValid = $this->isValidOrigin('https://api.example.com:8080', 'example.com');
     * // Returns true (subdomain match)
     * 
     * $isValid = $this->isValidOrigin('https://example.com', 'example.com');
     * // Returns true (exact match)
     * 
     * $isValid = $this->isValidOrigin('https://other.com', 'example.com');
     * // Returns false (no match)
     */
    private function isValidOrigin(string $origin, string $allowedDomain): bool
    {
        // Remove protocol if present
        $origin = preg_replace('/^https?:\/\//', '', $origin);
        $origin = preg_replace('/:\d+$/', '', $origin); // Remove port if present

        // Check exact match
        if ($origin === $allowedDomain) {
            return true;
        }

        // Check if it's a subdomain
        if (str_ends_with($origin, '.' . $allowedDomain)) {
            return true;
        }

        return false;
    }
}
