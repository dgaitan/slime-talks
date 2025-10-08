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
                ->assertJsonValidationErrors(['customer_uuids']);
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

    describe('Retrieve Channel', function () {
        it('can retrieve channel information when authenticated', function () {
            // Create customers first
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            // Create a channel
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Attach customers to the channel
            $channel->customers()->attach([$customer1->id, $customer2->id]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels/{$channel->uuid}");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'channel',
                    'id' => $channel->uuid,
                    'type' => 'general',
                    'name' => 'general',
                ])
                ->assertJsonStructure([
                    'object',
                    'id',
                    'type',
                    'name',
                    'created',
                    'livemode',
                ]);
        });

        it('returns 404 for non-existent channel', function () {
            $nonExistentUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels/{$nonExistentUuid}");

            $response->assertStatus(404);
        });

        it('returns 404 for channel from different client', function () {
            // Create another client and channel
            $otherClient = Client::factory()->create([
                'name' => 'Other Client',
                'domain' => 'other.com',
                'public_key' => 'other-public-key',
            ]);

            $otherChannel = Channel::factory()->create([
                'client_id' => $otherClient->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels/{$otherChannel->uuid}");

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->getJson("/api/v1/channels/{$channel->uuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
        });

        it('requires public key header', function () {
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels/{$channel->uuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
        });

        it('validates origin domain', function () {
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => 'unauthorized.com',
            ])->getJson("/api/v1/channels/{$channel->uuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
        });

        it('can retrieve custom channel', function () {
            // Create customers first
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => 'Project Discussion',
            ]);

            // Attach customers to the channel
            $channel->customers()->attach([$customer1->id, $customer2->id]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels/{$channel->uuid}");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'channel',
                    'id' => $channel->uuid,
                    'type' => 'custom',
                    'name' => 'Project Discussion',
                ]);
        });

        it('returns proper JSON structure', function () {
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels/{$channel->uuid}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'object',
                    'id',
                    'type',
                    'name',
                    'created',
                    'livemode',
                ])
                ->assertJsonFragment([
                    'object' => 'channel',
                    'id' => $channel->uuid,
                    'type' => 'general',
                    'name' => 'general',
                ]);
        });
    });

    describe('List Channels', function () {
        it('can list channels for authenticated client', function () {
            // Create multiple channels for the client
            $channel1 = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $channel2 = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => 'Project Discussion',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'object',
                    'data',
                    'has_more',
                    'total_count',
                ])
                ->assertJson([
                    'object' => 'list',
                ]);

            // Check that both channels are in the response
            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(2);
            expect($responseData['total_count'])->toBe(2);
            expect($responseData['has_more'])->toBeFalse();
        });

        it('only returns channels for authenticated client', function () {
            // Create channel for this client
            $thisChannel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Create another client and channel
            $otherClient = Client::factory()->create([
                'name' => 'Other Client',
                'domain' => 'other.com',
                'public_key' => 'other-public-key',
            ]);

            $otherChannel = Channel::factory()->create([
                'client_id' => $otherClient->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels');

            $response->assertStatus(200);

            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(1);
            expect($responseData['total_count'])->toBe(1);
            
            // Verify only this client's channel is returned
            $channelIds = collect($responseData['data'])->pluck('id')->toArray();
            expect($channelIds)->toContain($thisChannel->uuid);
            expect($channelIds)->not->toContain($otherChannel->uuid);
        });

        it('supports pagination parameters', function () {
            // Create multiple channels
            $channels = Channel::factory()->count(5)->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels?limit=3');

            $response->assertStatus(200);

            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(3);
            expect($responseData['total_count'])->toBe(5);
            expect($responseData['has_more'])->toBeTrue();
        });

        it('supports cursor-based pagination with starting_after', function () {
            // Create multiple channels
            $channels = Channel::factory()->count(5)->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Get the first channel's UUID for cursor pagination
            $firstChannel = $channels->first();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/channels?limit=2&starting_after={$firstChannel->uuid}");

            $response->assertStatus(200);

            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(2);
            expect($responseData['total_count'])->toBe(5);
            
            // Verify the first channel is not in the results (cursor pagination)
            $channelIds = collect($responseData['data'])->pluck('id')->toArray();
            expect($channelIds)->not->toContain($firstChannel->uuid);
        });

        it('returns empty list when no channels exist', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels');

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'data' => [],
                    'has_more' => false,
                    'total_count' => 0,
                ]);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/channels');

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
        });

        it('requires public key header', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels');

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
        });

        it('validates origin domain', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => 'unauthorized.com',
            ])->getJson('/api/v1/channels');

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
        });

        it('returns proper JSON structure for each channel', function () {
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => 'Project Discussion',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels');

            $response->assertStatus(200);

            $responseData = $response->json();
            $channelData = $responseData['data'][0];

            expect($channelData)->toHaveKeys([
                'object',
                'id',
                'type',
                'name',
                'created',
                'livemode',
            ]);

            expect($channelData['object'])->toBe('channel');
            expect($channelData['id'])->toBe($channel->uuid);
            expect($channelData['type'])->toBe('custom');
            expect($channelData['name'])->toBe('Project Discussion');
        });

        it('handles default pagination parameters', function () {
            // Create multiple channels
            Channel::factory()->count(15)->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson('/api/v1/channels');

            $response->assertStatus(200);

            $responseData = $response->json();
            expect($responseData['data'])->toHaveCount(10); // Default limit
            expect($responseData['total_count'])->toBe(15);
            expect($responseData['has_more'])->toBeTrue();
        });
    });
});
