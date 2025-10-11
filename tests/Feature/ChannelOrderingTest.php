<?php

declare(strict_types=1);

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Message;

describe('Channel Ordering by Activity', function () {
    beforeEach(function () {
        $this->client = Client::factory()->create([
            'domain' => 'example.com',
        ]);

        $this->token = $this->client->createToken('test-token')->plainTextToken;
    });

    it('orders channels by latest message activity', function () {
        // Create 3 customers
        $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
        $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);
        $customer3 = Customer::factory()->create(['client_id' => $this->client->id]);

        // Create 3 channels at different times
        $channel1 = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Channel 1',
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);
        $channel1->customers()->attach([$customer1->id, $customer2->id]);

        $channel2 = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Channel 2',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $channel2->customers()->attach([$customer1->id, $customer3->id]);

        $channel3 = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Channel 3',
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);
        $channel3->customers()->attach([$customer2->id, $customer3->id]);

        // Send message to channel1 (oldest channel) to make it most recent
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->postJson('/api/v1/messages', [
            'channel_uuid' => $channel1->uuid,
            'sender_uuid' => $customer1->uuid,
            'type' => 'text',
            'content' => 'Latest message in channel 1',
        ]);

        $response->assertStatus(201);

        // List channels - channel1 should be first now
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->getJson('/api/v1/channels');

        $response->assertStatus(200);
        
        $channels = $response->json('data');
        
        // Channel 1 should be first (most recent activity)
        expect($channels[0]['id'])->toBe($channel1->uuid->toString());
        expect($channels[0]['name'])->toBe('Channel 1');
        
        // Channel 3 should be second (created most recently, no new messages)
        expect($channels[1]['id'])->toBe($channel3->uuid->toString());
        
        // Channel 2 should be last
        expect($channels[2]['id'])->toBe($channel2->uuid->toString());
    });

    it('maintains order when multiple messages are sent', function () {
        // Create 2 customers
        $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
        $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

        // Create 2 channels
        $channelA = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Channel A',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);
        $channelA->customers()->attach([$customer1->id, $customer2->id]);

        $channelB = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Channel B',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);
        $channelB->customers()->attach([$customer1->id, $customer2->id]);

        // Send message to channel A
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->postJson('/api/v1/messages', [
            'channel_uuid' => $channelA->uuid,
            'sender_uuid' => $customer1->uuid,
            'type' => 'text',
            'content' => 'Message to channel A',
        ]);

        // Send message to channel B (after channel A)
        sleep(1); // Ensure different timestamps
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->postJson('/api/v1/messages', [
            'channel_uuid' => $channelB->uuid,
            'sender_uuid' => $customer2->uuid,
            'type' => 'text',
            'content' => 'Message to channel B',
        ]);

        // List channels - channel B should be first
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->getJson('/api/v1/channels');

        $response->assertStatus(200);
        
        $channels = $response->json('data');
        
        // Channel B should be first (most recent message)
        expect($channels[0]['id'])->toBe($channelB->uuid->toString());
        
        // Channel A should be second
        expect($channels[1]['id'])->toBe($channelA->uuid->toString());
    });

    it('orders customer channels by latest activity', function () {
        // Create customers
        $customer1 = Customer::factory()->create(['client_id' => $this->client->id]);
        $customer2 = Customer::factory()->create(['client_id' => $this->client->id]);

        // Create 2 channels where customer1 participates
        $oldChannel = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Old Channel',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);
        $oldChannel->customers()->attach([$customer1->id, $customer2->id]);

        $newChannel = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'New Channel',
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);
        $newChannel->customers()->attach([$customer1->id, $customer2->id]);

        // Send message to old channel to make it recent
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->postJson('/api/v1/messages', [
            'channel_uuid' => $oldChannel->uuid,
            'sender_uuid' => $customer1->uuid,
            'type' => 'text',
            'content' => 'New message in old channel',
        ]);

        // Get customer's channels
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->getJson("/api/v1/channels/customer/{$customer1->uuid}");

        $response->assertStatus(200);
        
        $channels = $response->json('data');
        
        // Old channel should be first (most recent message)
        expect($channels[0]['id'])->toBe($oldChannel->uuid->toString());
        expect($channels[0]['name'])->toBe('Old Channel');
        
        // New channel should be second
        expect($channels[1]['id'])->toBe($newChannel->uuid->toString());
        expect($channels[1]['name'])->toBe('New Channel');
    });
});

