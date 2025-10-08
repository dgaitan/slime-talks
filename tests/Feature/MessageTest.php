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

    describe('Retrieve Channel Messages', function () {
        it('can retrieve messages from a channel', function () {
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

            // Create some messages
            $message1 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'First message',
            ]);

            $message2 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer2->id,
                'content' => 'Second message',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'object',
                    'data' => [
                        '*' => [
                            'object',
                            'id',
                            'type',
                            'content',
                            'metadata',
                            'created',
                            'livemode',
                        ]
                    ],
                    'has_more',
                    'total_count',
                ])
                ->assertJson([
                    'object' => 'list',
                    'has_more' => false,
                    'total_count' => 2,
                ]);

            // Verify messages are ordered by creation time (oldest first)
            $messages = $response->json('data');
            expect($messages)->toHaveCount(2);
            expect($messages[0]['content'])->toBe('First message');
            expect($messages[1]['content'])->toBe('Second message');
        });

        it('returns empty list when channel has no messages', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Attach customer to channel
            $channel->customers()->attach([$customer->id]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'data' => [],
                    'has_more' => false,
                    'total_count' => 0,
                ]);
        });

        it('returns 404 for non-existent channel', function () {
            $nonExistentChannelUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$nonExistentChannelUuid}");

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
            ])->getJson("/api/v1/messages/channel/{$otherChannel->uuid}");

            $response->assertStatus(404);
        });

        it('supports pagination parameters', function () {
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

            // Create multiple messages
            for ($i = 1; $i <= 5; $i++) {
                Message::factory()->create([
                    'client_id' => $this->client->id,
                    'channel_id' => $channel->id,
                    'sender_id' => $customer1->id,
                    'content' => "Message {$i}",
                ]);
            }

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}?limit=3");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'has_more' => true,
                    'total_count' => 5,
                ]);

            $messages = $response->json('data');
            expect($messages)->toHaveCount(3);
        });

        it('supports cursor-based pagination with starting_after', function () {
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

            // Create messages with distinct timestamps
            $message1 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'First message',
            ]);
            usleep(1000); // Ensure different timestamps

            $message2 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Second message',
            ]);
            usleep(1000);

            $message3 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Third message',
            ]);

            // Get first page
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}?limit=2");

            $response->assertStatus(200);
            $firstPageMessages = $response->json('data');
            expect($firstPageMessages)->toHaveCount(2);

            // Get second page using starting_after
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}?limit=2&starting_after={$firstPageMessages[1]['id']}");

            $response->assertStatus(200);
            $secondPageMessages = $response->json('data');
            expect($secondPageMessages)->toHaveCount(1);
            expect($secondPageMessages[0]['content'])->toBe('Third message');
        });

        it('requires authentication', function () {
            $channelUuid = \Illuminate\Support\Str::uuid();

            $response = $this->getJson("/api/v1/messages/channel/{$channelUuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
        });

        it('requires public key header', function () {
            $channelUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channelUuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
        });

        it('validates origin domain', function () {
            $channelUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => 'unauthorized.com',
            ])->getJson("/api/v1/messages/channel/{$channelUuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
        });

        it('returns proper JSON structure for each message', function () {
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

            // Create a message
            Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Test message',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}");

            $response->assertStatus(200);
            $messages = $response->json('data');
            expect($messages)->toHaveCount(1);
            
            $message = $messages[0];
            expect($message)->toHaveKeys([
                'object',
                'id',
                'type',
                'content',
                'metadata',
                'created',
                'livemode',
            ]);
            expect($message['object'])->toBe('message');
        });

        it('handles default pagination parameters', function () {
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

            // Create a message
            Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Test message',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/channel/{$channel->uuid}");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'has_more' => false,
                    'total_count' => 1,
                ]);
        });
    });

    describe('Retrieve Customer Messages', function () {
        it('can retrieve messages for a customer', function () {
            // Create customers and channels
            $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
            $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);
            
            $channel1 = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            $channel2 = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'custom',
                'name' => 'support',
            ]);

            // Attach customers to channels
            $channel1->customers()->attach([$customer1->id, $customer2->id]);
            $channel2->customers()->attach([$customer1->id, $customer2->id]);

            // Create messages in different channels
            $message1 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel1->id,
                'sender_id' => $customer1->id,
                'content' => 'Message in channel 1',
            ]);

            $message2 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel2->id,
                'sender_id' => $customer1->id,
                'content' => 'Message in channel 2',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer1->uuid}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'object',
                    'data' => [
                        '*' => [
                            'object',
                            'id',
                            'type',
                            'content',
                            'metadata',
                            'created',
                            'livemode',
                        ]
                    ],
                    'has_more',
                    'total_count',
                ])
                ->assertJson([
                    'object' => 'list',
                    'has_more' => false,
                    'total_count' => 2,
                ]);

            // Verify messages are ordered by creation time (newest first)
            $messages = $response->json('data');
            expect($messages)->toHaveCount(2);
            // Messages should be ordered newest first
            expect($messages[0]['content'])->toBe('Message in channel 2');
            expect($messages[1]['content'])->toBe('Message in channel 1');
        });

        it('returns empty list when customer has no messages', function () {
            $customer = Customer::factory()->create(['client_id' => $this->client->id]);
            
            $channel = Channel::factory()->create([
                'client_id' => $this->client->id,
                'type' => 'general',
                'name' => 'general',
            ]);

            // Attach customer to channel but don't create any messages
            $channel->customers()->attach([$customer->id]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer->uuid}");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'data' => [],
                    'has_more' => false,
                    'total_count' => 0,
                ]);
        });

        it('returns 404 for non-existent customer', function () {
            $nonExistentCustomerUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$nonExistentCustomerUuid}");

            $response->assertStatus(404);
        });

        it('returns 404 for customer from different client', function () {
            // Create another client and customer
            $otherClient = Client::factory()->create([
                'name' => 'Other Client',
                'domain' => 'other.com',
                'public_key' => 'other-public-key',
            ]);

            $otherCustomer = Customer::factory()->create(['client_id' => $otherClient->id]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$otherCustomer->uuid}");

            $response->assertStatus(404);
        });

        it('supports pagination parameters', function () {
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

            // Create multiple messages
            for ($i = 1; $i <= 5; $i++) {
                Message::factory()->create([
                    'client_id' => $this->client->id,
                    'channel_id' => $channel->id,
                    'sender_id' => $customer1->id,
                    'content' => "Message {$i}",
                ]);
            }

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer1->uuid}?limit=3");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'has_more' => true,
                    'total_count' => 5,
                ]);

            $messages = $response->json('data');
            expect($messages)->toHaveCount(3);
        });

        it('supports cursor-based pagination with starting_after', function () {
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

            // Create messages with distinct timestamps
            $message1 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'First message',
            ]);
            usleep(1000); // Ensure different timestamps

            $message2 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Second message',
            ]);
            usleep(1000);

            $message3 = Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Third message',
            ]);

            // Get first page
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer1->uuid}?limit=2");

            $response->assertStatus(200);
            $firstPageMessages = $response->json('data');
            expect($firstPageMessages)->toHaveCount(2);

            // Get second page using starting_after
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer1->uuid}?limit=2&starting_after={$firstPageMessages[1]['id']}");

            $response->assertStatus(200);
            $secondPageMessages = $response->json('data');
            expect($secondPageMessages)->toHaveCount(1);
            expect($secondPageMessages[0]['content'])->toBe('First message');
        });

        it('requires authentication', function () {
            $customerUuid = \Illuminate\Support\Str::uuid();

            $response = $this->getJson("/api/v1/messages/customer/{$customerUuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing or invalid Authorization header']);
        });

        it('requires public key header', function () {
            $customerUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customerUuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Missing X-Public-Key header']);
        });

        it('validates origin domain', function () {
            $customerUuid = \Illuminate\Support\Str::uuid();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => 'unauthorized.com',
            ])->getJson("/api/v1/messages/customer/{$customerUuid}");

            $response->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized - Invalid origin domain']);
        });

        it('returns proper JSON structure for each message', function () {
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

            // Create a message
            Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Test message',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer1->uuid}");

            $response->assertStatus(200);
            $messages = $response->json('data');
            expect($messages)->toHaveCount(1);
            
            $message = $messages[0];
            expect($message)->toHaveKeys([
                'object',
                'id',
                'type',
                'content',
                'metadata',
                'created',
                'livemode',
            ]);
            expect($message['object'])->toBe('message');
        });

        it('handles default pagination parameters', function () {
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

            // Create a message
            Message::factory()->create([
                'client_id' => $this->client->id,
                'channel_id' => $channel->id,
                'sender_id' => $customer1->id,
                'content' => 'Test message',
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Public-Key' => $this->client->public_key,
                'Origin' => $this->client->domain,
            ])->getJson("/api/v1/messages/customer/{$customer1->uuid}");

            $response->assertStatus(200)
                ->assertJson([
                    'object' => 'list',
                    'has_more' => false,
                    'total_count' => 1,
                ]);
        });
    });
});
