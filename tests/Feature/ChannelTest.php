<?php

declare(strict_types=1);

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Channel API', function () {
    beforeEach(function () {
        $this->client = Client::factory()->create();
        $this->token = $this->client->createToken('test-token')->plainTextToken;
    });

    describe('Create Channel', function () {
        it('can create a general channel with valid customers', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'object',
                    'id',
                    'type',
                    'name',
                    'created',
                    'livemode',
                ])
                ->assertJson([
                    'object' => 'channel',
                    'type' => 'general',
                    'name' => 'general',
                ]);

            $this->assertDatabaseHas('channels', [
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Verify customers are attached to the channel
            $channel = Channel::where('client_id', $this->client->id)->first();
            expect($channel->customers)->toHaveCount(2);
            expect($channel->customers->pluck('id')->toArray())->toContain($customer1->id, $customer2->id);
        });

        it('rejects if customers do not exist', function () {
            $nonExistentUuid = '00000000-0000-0000-0000-000000000000';

            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$nonExistentUuid, 'another-non-existent-uuid'],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids.0', 'customer_uuids.1']);
        });

        it('rejects if customers belong to different clients', function () {
            $otherClient = Client::factory()->create();
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $otherClient->id]);

            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids']);
        });

        it('prevents duplicate general channels between same customers', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            // Create first channel
            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            // Try to create duplicate channel
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids']);
        });

        it('requires authentication', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(401);
        });

        it('requires public key header', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(401);
        });

        it('validates required fields', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['type', 'customer_uuids']);
        });

        it('validates channel type', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'invalid-type',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
        });

        it('validates customer_uuids array format', function () {
            $channelData = [
                'type' => 'general',
                'customer_uuids' => 'not-an-array',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids']);
        });

        it('validates minimum number of customers', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid], // Only one customer
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids']);
        });

        it('validates maximum number of customers', function () {
            $customers = Customer::factory()->count(10)->create(['client_id' => $this->client->id]);
            $customerUuids = $customers->pluck('uuid')->toArray();

            $channelData = [
                'type' => 'general',
                'customer_uuids' => $customerUuids, // Too many customers
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids']);
        });
    });
});
