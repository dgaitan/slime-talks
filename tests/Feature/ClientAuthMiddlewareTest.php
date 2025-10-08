<?php

use App\Models\Client;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Create a test client
    $this->client = Client::factory()->create([
        'name' => 'Test Client',
        'domain' => 'test.com',
        'public_key' => 'pk_test_1234567890',
    ]);

    // Create a test token for the client
    $this->token = $this->client->createToken('test-token')->plainTextToken;

    // Create a test route that uses the middleware
    Route::middleware(['client.auth'])->get('/test', function () {
        return response()->json(['authenticated' => true, 'client_id' => auth()->id()]);
    });
});

describe('Client Authentication Middleware', function () {
    it('allows access with valid token and public key', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'client_id' => $this->client->id,
            ]);
    });

    it('rejects requests without Authorization header', function () {
        $response = $this->withHeaders([
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
    });

    it('rejects requests with invalid Authorization header format', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Invalid ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
    });

    it('rejects requests without X-Public-Key header', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
    });

    it('rejects requests with invalid public key', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => 'pk_invalid_key',
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Invalid public key']);
    });

    it('rejects requests with token that does not belong to the client', function () {
        // Create another client with a different token
        $otherClient = Client::factory()->create([
            'name' => 'Other Client',
            'domain' => 'other.com',
            'public_key' => 'pk_other_1234567890',
        ]);
        $otherToken = $otherClient->createToken('other-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken,
            'X-Public-Key' => $this->client->public_key, // Using first client's public key
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Invalid token for this client']);
    });

    it('rejects requests with invalid origin domain', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => 'invalid.com',
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
    });

    it('allows requests with valid subdomain', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => 'api.test.com',
        ])->get('/test');

        $response->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'client_id' => $this->client->id,
            ]);
    });

    it('allows requests without Origin header when Host header is valid', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Host' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'client_id' => $this->client->id,
            ]);
    });

    it('allows requests with protocol and port in origin', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => 'https://test.com:8080',
        ])->get('/test');

        $response->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'client_id' => $this->client->id,
            ]);
    });

    it('rejects requests with expired token', function () {
        // Create a token that expires in the past
        $expiredToken = $this->client->createToken('expired-token', ['*'], now()->subDay())->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Token expired']);
    });

    it('updates token last_used_at timestamp on successful authentication', function () {
        $tokenRecord = $this->client->tokens()->where('name', 'test-token')->first();
        $originalLastUsed = $tokenRecord->last_used_at;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->get('/test');

        $response->assertStatus(200);

        $tokenRecord->refresh();
        expect($tokenRecord->last_used_at)->not->toBeNull();
        expect($tokenRecord->last_used_at)->not->toEqual($originalLastUsed);
    });
});
