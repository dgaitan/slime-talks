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

    describe('Create Custom Channel', function () {
        it('can create a custom channel with valid data', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'custom',
                'name' => 'Project Discussion',
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
                    'type' => 'custom',
                    'name' => 'Project Discussion',
                ]);

            $this->assertDatabaseHas('channels', [
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => 'Project Discussion',
            ]);

            // Verify customers are attached to the channel
            $channel = Channel::where('client_id', $this->client->id)
                ->where('type', 'custom')
                ->first();
            expect($channel->customers)->toHaveCount(2);
            expect($channel->customers->pluck('id')->toArray())->toContain($customer1->id, $customer2->id);
        });

        it('automatically creates general channel if it does not exist', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'custom',
                'name' => 'Project Discussion',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(201);

            // Verify both custom and general channels exist
            $this->assertDatabaseHas('channels', [
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => 'Project Discussion',
            ]);

            $this->assertDatabaseHas('channels', [
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Verify both channels have the same customers
            $customChannel = Channel::where('client_id', $this->client->id)
                ->where('type', 'custom')
                ->first();
            $generalChannel = Channel::where('client_id', $this->client->id)
                ->where('type', 'general')
                ->first();

            expect($customChannel->customers->pluck('id')->toArray())
                ->toEqual($generalChannel->customers->pluck('id')->toArray());
        });

        it('does not create duplicate general channel if it already exists', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            // First create a general channel
            $generalChannelData = [
                'type' => 'general',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $generalChannelData);

            // Count existing general channels
            $initialGeneralChannels = Channel::where('client_id', $this->client->id)
                ->where('type', 'general')
                ->count();

            // Now create a custom channel
            $customChannelData = [
                'type' => 'custom',
                'name' => 'Project Discussion',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $customChannelData);

            $response->assertStatus(201);

            // Verify no additional general channel was created
            $finalGeneralChannels = Channel::where('client_id', $this->client->id)
                ->where('type', 'general')
                ->count();

            expect($finalGeneralChannels)->toBe($initialGeneralChannels);
        });

        it('rejects if customers do not exist for custom channel', function () {
            $nonExistentUuid = '00000000-0000-0000-0000-000000000000';

            $channelData = [
                'type' => 'custom',
                'name' => 'Project Discussion',
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

        it('rejects if customers belong to different clients for custom channel', function () {
            $otherClient = Client::factory()->create();
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $otherClient->id]);

            $channelData = [
                'type' => 'custom',
                'name' => 'Project Discussion',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_uuids.1']);
        });

        it('requires authentication for custom channel', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'custom',
                'name' => 'Project Discussion',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(401);
        });

        it('validates required name field for custom channel', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'custom',
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
                // Missing 'name' field
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates name field is not empty for custom channel', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'custom',
                'name' => '', // Empty name
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates name field maximum length for custom channel', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelData = [
                'type' => 'custom',
                'name' => str_repeat('a', 256), // Too long name (over 255 chars)
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for different clients', function () {
            $otherClient = Client::factory()->create();
            $otherToken = $otherClient->createToken('test-token')->plainTextToken;

            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);
            $otherCustomer1 = Customer::factory()->create(['client_id' => $otherClient->id]);
            $otherCustomer2 = Customer::factory()->create(['client_id' => $otherClient->id]);

            $channelName = 'Project Discussion';

            // Create custom channel for first client
            $channelData1 = [
                'type' => 'custom',
                'name' => $channelName,
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $response1 = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData1);

            $response1->assertStatus(201);

            // Create custom channel for second client with same name
            $channelData2 = [
                'type' => 'custom',
                'name' => $channelName,
                'customer_uuids' => [$otherCustomer1->uuid, $otherCustomer2->uuid],
            ];

            $response2 = $this->withHeaders([
                'Authorization' => 'Bearer ' . $otherToken,
                'X-Public-Key' => $otherClient->public_key,
                'Origin' => $otherClient->domain,
            ])->postJson('/api/v1/channels', $channelData2);

            $response2->assertStatus(201);

            // Verify both channels exist with the same name
            $this->assertDatabaseHas('channels', [
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => $channelName,
            ]);

            $this->assertDatabaseHas('channels', [
                'client_id' => $otherClient->id,
                'type' => 'custom',
                'name' => $channelName,
            ]);
        });

        it('prevents duplicate custom channels with same name for same client', function () {
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channelName = 'Project Discussion';

            // Create first custom channel
            $channelData = [
                'type' => 'custom',
                'name' => $channelName,
                'customer_uuids' => [$customer1->uuid, $customer2->uuid],
            ];

            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            // Try to create duplicate custom channel with same name
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/channels', $channelData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });
    });
});
