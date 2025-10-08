<?php

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Message;

beforeEach(function () {
    $this->client = Client::factory()->create([
        'name' => 'Test Client',
        'domain' => 'test.com',
        'public_key' => 'test-public-key',
    ]);

    $this->token = $this->client->createToken('test-token')->plainTextToken;
});

describe('Message API', function () {
    describe('Send Message', function () {
        it('can send text message', function () {
            // Create customers and channel
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);
            
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Attach customers to channel
            $channel->customers()->attach([$customer1->id, $customer2->id]);

            $messageData = [
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $customer1->uuid,
                'type' => 'text',
                'content' => 'Hello, this is a test message!',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'object',
                    'id',
                    'type',
                    'content',
                    'metadata',
                    'created',
                    'livemode',
                ])
                ->assertJson([
                    'object' => 'message',
                    'type' => 'text',
                    'content' => 'Hello, this is a test message!',
                ]);

            // Verify message was created in database
            $this->assertDatabaseHas('messages', [
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'type' => 'text',
                'content' => 'Hello, this is a test message!',
            ]);

            // Verify UUID was generated
            $message = Message::where('content', 'Hello, this is a test message!')->first();
            expect($message->uuid)->not->toBeNull();
        });

        it('can send message with metadata', function () {
            // Create customers and channel
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);
            
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Attach customers to channel
            $channel->customers()->attach([$customer1->id, $customer2->id]);

            $messageData = [
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $customer1->uuid,
                'type' => 'text',
                'content' => 'Message with metadata',
                'metadata' => [
                    'priority' => 'high',
                    'tags' => ['important', 'urgent'],
                ],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(201)
                ->assertJson([
                    'object' => 'message',
                    'type' => 'text',
                    'content' => 'Message with metadata',
                    'metadata' => [
                        'priority' => 'high',
                        'tags' => ['important', 'urgent'],
                    ],
                ]);

            // Verify metadata was stored
            $this->assertDatabaseHas('messages', [
                'content' => 'Message with metadata',
                'metadata' => json_encode([
                    'priority' => 'high',
                    'tags' => ['important', 'urgent'],
                ]),
            ]);
        });

        it('rejects if sender not in channel', function () {
            // Create customers and channel
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer3 = Customer::factory()->create(['client_id' => $this->client->id]);
            
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Attach only customer1 and customer2 to channel (not customer3)
            $channel->customers()->attach([$customer1->id, $customer2->id]);

            $messageData = [
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $customer3->uuid, // customer3 is not in channel
                'type' => 'text',
                'content' => 'This should fail',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['sender_uuid']);
        });

        it('rejects if channel does not exist', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            $nonExistentChannelUuid = \Illuminate\Support\Str::uuid();

            $messageData = [
                'channel_uuid' => $nonExistentChannelUuid,
                'sender_uuid' => $customer->uuid,
                'type' => 'text',
                'content' => 'This should fail',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['channel_uuid']);
        });

        it('rejects if channel belongs to different client', function () {
            // Create customer for this client
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);

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

            $messageData = [
                'channel_uuid' => $otherChannel->uuid,
                'sender_uuid' => $customer->uuid,
                'type' => 'text',
                'content' => 'This should fail',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['channel_uuid']);
        });

        it('requires authentication', function () {
            $messageData = [
                'channel_uuid' => 'some-uuid',
                'sender_uuid' => 'some-uuid',
                'type' => 'text',
                'content' => 'This should fail',
            ];

            $response = $this->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
        });

        it('requires public key header', function () {
            $messageData = [
                'channel_uuid' => 'some-uuid',
                'sender_uuid' => 'some-uuid',
                'type' => 'text',
                'content' => 'This should fail',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
        });

        it('validates origin domain', function () {
            $messageData = [
                'channel_uuid' => 'some-uuid',
                'sender_uuid' => 'some-uuid',
                'type' => 'text',
                'content' => 'This should fail',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => 'unauthorized.com',
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
        });

        it('validates required fields', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['channel_uuid', 'sender_uuid', 'type', 'content']);
        });

        it('validates message type', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            $channel = Channel::factory()->create(['client_id' => $this->client->id]);
            $channel->customers()->attach([$customer->id]);

            $messageData = [
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $customer->uuid,
                'type' => 'invalid_type',
                'content' => 'This should fail',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
        });

        it('validates content is not empty', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            $channel = Channel::factory()->create(['client_id' => $this->client->id]);
            $channel->customers()->attach([$customer->id]);

            $messageData = [
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $customer->uuid,
                'type' => 'text',
                'content' => '',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
        });

        it('supports different message types', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            $channel = Channel::factory()->create(['client_id' => $this->client->id]);
            $channel->customers()->attach([$customer->id]);

            $messageTypes = ['text', 'image', 'file'];

            foreach ($messageTypes as $type) {
                $messageData = [
                    'channel_uuid' => $channel->uuid,
                    'sender_uuid' => $customer->uuid,
                    'type' => $type,
                    'content' => "Test {$type} message",
                ];

                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'X-Public-Key' => $this->client->public_key,
                    'Origin' => $this->client->domain,
                ])->postJson('/api/v1/messages', $messageData);

                $response->assertStatus(201)
                    ->assertJson([
                        'object' => 'message',
                        'type' => $type,
                        'content' => "Test {$type} message",
                    ]);
            }
        });

        it('returns proper JSON structure', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            $channel = Channel::factory()->create(['client_id' => $this->client->id]);
            $channel->customers()->attach([$customer->id]);

            $messageData = [
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $customer->uuid,
                'type' => 'text',
                'content' => 'Test message',
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->postJson('/api/v1/messages', $messageData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'object',
                    'id',
                    'type',
                    'content',
                    'metadata',
                    'created',
                    'livemode',
                ])
                ->assertJsonFragment([
                    'object' => 'message',
                    'type' => 'text',
                    'content' => 'Test message',
                ]);
        });
    });
});
