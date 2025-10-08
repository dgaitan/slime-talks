<?php

use App\Models\Client;
use Laravel\Sanctum\Sanctum;

describe('Client API', function () {
    beforeEach(function () {
        $this->client = Client::factory()->create();
    });

    it('can retrieve client information when authenticated', function () {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->getJson('/api/v1/client/' . $this->client->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->client->id,
                'uuid' => $this->client->uuid,
                'name' => $this->client->name,
                'domain' => $this->client->domain,
                'public_key' => $this->client->public_key,
            ]);
    });

    it('requires authentication for protected endpoints', function () {
        $response = $this->getJson('/api/v1/client/' . $this->client->uuid);
        $response->assertStatus(401);
    });

    it('rejects requests without public key header', function () {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Origin' => $this->client->domain,
        ])->getJson('/api/v1/client/' . $this->client->uuid);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
    });

    it('rejects requests with invalid public key', function () {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Public-Key' => 'invalid-public-key',
            'Origin' => $this->client->domain,
        ])->getJson('/api/v1/client/' . $this->client->uuid);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Invalid public key']);
    });

    it('rejects requests from invalid origin', function () {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => 'malicious-site.com',
        ])->getJson('/api/v1/client/' . $this->client->uuid);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
    });

    it('accepts requests from subdomains', function () {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => 'api.' . $this->client->domain,
        ])->getJson('/api/v1/client/' . $this->client->uuid);

        $response->assertStatus(200);
    });

    it('returns 404 for non-existent client', function () {
        $token = $this->client->createToken('test-token')->plainTextToken;
        $nonExistentUuid = '00000000-0000-0000-0000-000000000000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->getJson('/api/v1/client/' . $nonExistentUuid);

        $response->assertStatus(404);
    });
});
