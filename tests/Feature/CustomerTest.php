<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Customer API', function () {
    beforeEach(function () {
        $this->client = Client::factory()->create();
        $this->token = $this->client->createToken('test-token')->plainTextToken;
    });

    describe('Create Customer', function () {
        it('can create a customer with valid data', function () {
            $customerData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'metadata' => [
                    'avatar' => 'https://example.com/avatar.jpg',
                    'preferences' => [
                        'notifications' => true,
                    ],
                ],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/customers', $customerData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'object',
                    'id',
                    'name',
                    'email',
                    'metadata',
                    'created',
                    'livemode',
                ])
                ->assertJson([
                    'object' => 'customer',
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]);

            $this->assertDatabaseHas('customers', [
                'client_id' => $this->client->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        it('rejects invalid email format', function () {
            $customerData = [
                'name' => 'John Doe',
                'email' => 'invalid-email',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/customers', $customerData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('rejects duplicate email for same client', function () {
            // Create first customer
            Customer::factory()->create([
                'client_id' => $this->client->id,
                'email' => 'john@example.com',
            ]);

            $customerData = [
                'name' => 'Jane Doe',
                'email' => 'john@example.com', // Same email
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/customers', $customerData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('allows same email for different clients', function () {
            $otherClient = Client::factory()->create();
            
            // Create customer for other client
            Customer::factory()->create([
                'client_id' => $otherClient->id,
                'email' => 'john@example.com',
            ]);

            $customerData = [
                'name' => 'John Doe',
                'email' => 'john@example.com', // Same email, different client
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/customers', $customerData);

            $response->assertStatus(201);
        });

        it('requires authentication', function () {
            $customerData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ];

            $response = $this->postJson('/api/v1/customers', $customerData);

            $response->assertStatus(401);
        });

        it('requires public key header', function () {
            $customerData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/customers', $customerData);

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
        });

        it('validates required fields', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/customers', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email']);
        });
    });

    describe('Get Customer', function () {
        it('can retrieve customer information when authenticated', function () {
            $customer = Customer::factory()->create([
                'client_id' => $this->client->id,
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/customers/' . $customer->uuid);

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'customer',
                    'id' => $customer->uuid,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ]);
        });

        it('returns 404 for non-existent customer', function () {
            $nonExistentUuid = '00000000-0000-0000-0000-000000000000';

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/customers/' . $nonExistentUuid);

            $response->assertStatus(404);
        });

        it('returns 404 for customer from different client', function () {
            $otherClient = Client::factory()->create();
            $customer = Customer::factory()->create([
                'client_id' => $otherClient->id,
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/customers/' . $customer->uuid);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $customer = Customer::factory()->create([
                'client_id' => $this->client->id,
            ]);

            $response = $this->getJson('/api/v1/customers/' . $customer->uuid);

            $response->assertStatus(401);
        });
    });

    describe('List Customers', function () {
        it('can list customers for authenticated client', function () {
            // Create customers for this client
            $customers = Customer::factory()->count(3)->create([
                'client_id' => $this->client->id,
            ]);

            // Create customer for different client (should not appear)
            $otherClient = Client::factory()->create();
            Customer::factory()->create([
                'client_id' => $otherClient->id,
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/customers');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'object',
                    'data',
                    'has_more',
                    'total_count',
                ])
                ->assertJson([
                    'object' => 'list',
                    'total_count' => 3,
                ]);

            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(3);
        });

        it('supports pagination', function () {
            // Create 15 customers
            Customer::factory()->count(15)->create([
                'client_id' => $this->client->id,
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/customers?limit=10');

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'total_count' => 15,
                ]);

            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(10);
            expect($responseData['has_more'])->toBeTrue();
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/customers');

            $response->assertStatus(401);
        });
    });
});
