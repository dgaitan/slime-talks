<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for Authorization header
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized - Missing or invalid Authorization header'], 401);
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

        // Check for Public Key header
        $publicKey = $request->header('X-Public-Key');
        if (!$publicKey) {
            return response()->json(['error' => 'Unauthorized - Missing X-Public-Key header'], 401);
        }

        // Find client by public key
        $client = Client::where('public_key', $publicKey)->first();
        if (!$client) {
            return response()->json(['error' => 'Unauthorized - Invalid public key'], 401);
        }

        // Verify the token belongs to this client
        $tokenRecord = $client->tokens()->where('token', hash('sha256', $token))->first();
        if (!$tokenRecord) {
            return response()->json(['error' => 'Unauthorized - Invalid token for this client'], 401);
        }

        // Check origin domain
        $origin = $request->header('Origin') ?? $request->header('Host');
        if ($origin) {
            $allowedDomain = $client->domain;
            
            // Check if the origin matches the client's domain or is a subdomain
            if (!$this->isValidOrigin($origin, $allowedDomain)) {
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
     * Check if the origin is valid for the client's domain
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
