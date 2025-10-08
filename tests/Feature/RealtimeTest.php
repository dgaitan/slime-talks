<?php

use App\Events\MessageSent;
use App\Events\TypingStarted;
use App\Events\TypingStopped;
use App\Events\UserJoinedChannel;
use App\Events\UserLeftChannel;
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

describe('Real-time Messaging', function () {
    it('can broadcast message sent event', function () {
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

        // Create message
        $message = Message::factory()->create([
            'client_id' => $this->client->id,
            'channel_id' => $channel->id,
            'sender_id' => $customer1->id,
            'content' => 'Test message',
        ]);

        // Test that MessageSent event is broadcast
        Event::fake();
        
        broadcast(new MessageSent($message));
        
        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    });

    it('can broadcast typing started event', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        Event::fake();
        
        broadcast(new TypingStarted($customer, $channel));
        
        Event::assertDispatched(TypingStarted::class, function ($event) use ($customer, $channel) {
            return $event->customer->id === $customer->id && 
                   $event->channel->id === $channel->id;
        });
    });

    it('can broadcast typing stopped event', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        Event::fake();
        
        broadcast(new TypingStopped($customer, $channel));
        
        Event::assertDispatched(TypingStopped::class, function ($event) use ($customer, $channel) {
            return $event->customer->id === $customer->id && 
                   $event->channel->id === $channel->id;
        });
    });

    it('can broadcast user joined channel event', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        Event::fake();
        
        broadcast(new UserJoinedChannel($customer, $channel));
        
        Event::assertDispatched(UserJoinedChannel::class, function ($event) use ($customer, $channel) {
            return $event->customer->id === $customer->id && 
                   $event->channel->id === $channel->id;
        });
    });

    it('can broadcast user left channel event', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        Event::fake();
        
        broadcast(new UserLeftChannel($customer, $channel));
        
        Event::assertDispatched(UserLeftChannel::class, function ($event) use ($customer, $channel) {
            return $event->customer->id === $customer->id && 
                   $event->channel->id === $channel->id;
        });
    });

    it('broadcasts message when sending via API', function () {
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

        Event::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Public-Key' => $this->client->public_key,
            'Origin' => $this->client->domain,
        ])->postJson('/api/v1/messages', [
            'channel_uuid' => $channel->uuid,
            'sender_uuid' => $customer1->uuid,
            'type' => 'text',
            'content' => 'Test message for broadcasting',
        ]);

        $response->assertStatus(201);
        
        // Verify that MessageSent event was dispatched
        Event::assertDispatched(MessageSent::class);
    });

    it('has correct broadcast channel names', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        $messageSentEvent = new MessageSent(Message::factory()->create([
            'client_id' => $this->client->id,
            'channel_id' => $channel->id,
            'sender_id' => $customer->id,
        ]));

        $channels = $messageSentEvent->broadcastOn();
        
        expect($channels)->toHaveCount(1);
        expect($channels[0]->name)->toBe("private-channel.{$channel->uuid}");
    });

    it('has correct presence channel names', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        $userJoinedEvent = new UserJoinedChannel($customer, $channel);

        $channels = $userJoinedEvent->broadcastOn();
        
        expect($channels)->toHaveCount(2);
        expect($channels[0]->name)->toBe("private-channel.{$channel->uuid}");
        expect($channels[1]->name)->toBe("presence-presence.channel.{$channel->uuid}");
    });

    it('broadcasts correct event names', function () {
        $customer = Customer::factory()->create(['client_id' => $this->client->id]);
        $channel = Channel::factory()->create(['client_id' => $this->client->id]);

        $messageSentEvent = new MessageSent(Message::factory()->create([
            'client_id' => $this->client->id,
            'channel_id' => $channel->id,
            'sender_id' => $customer->id,
        ]));
        $typingStartedEvent = new TypingStarted($customer, $channel);
        $typingStoppedEvent = new TypingStopped($customer, $channel);
        $userJoinedEvent = new UserJoinedChannel($customer, $channel);
        $userLeftEvent = new UserLeftChannel($customer, $channel);

        expect($messageSentEvent->broadcastAs())->toBe('message.sent');
        expect($typingStartedEvent->broadcastAs())->toBe('typing.started');
        expect($typingStoppedEvent->broadcastAs())->toBe('typing.stopped');
        expect($userJoinedEvent->broadcastAs())->toBe('user.joined');
        expect($userLeftEvent->broadcastAs())->toBe('user.left');
    });

    it('broadcasts correct data structure', function () {
        $customer = Customer::factory()->create([
            'client_id' => $this->client->id,
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
        
        $channel = Channel::factory()->create([
            'client_id' => $this->client->id,
            'type' => 'custom',
            'name' => 'Test Channel',
        ]);

        $message = Message::factory()->create([
            'client_id' => $this->client->id,
            'channel_id' => $channel->id,
            'sender_id' => $customer->id,
            'type' => 'text',
            'content' => 'Test message content',
            'metadata' => ['priority' => 'high'],
        ]);

        $messageSentEvent = new MessageSent($message);
        $broadcastData = $messageSentEvent->broadcastWith();

        expect($broadcastData)->toHaveKey('message');
        expect($broadcastData['message'])->toHaveKey('id');
        expect($broadcastData['message'])->toHaveKey('type');
        expect($broadcastData['message'])->toHaveKey('content');
        expect($broadcastData['message'])->toHaveKey('sender');
        expect($broadcastData['message'])->toHaveKey('channel');
        expect($broadcastData['message'])->toHaveKey('created_at');

        expect($broadcastData['message']['sender'])->toHaveKey('id');
        expect($broadcastData['message']['sender'])->toHaveKey('name');
        expect($broadcastData['message']['channel'])->toHaveKey('id');
        expect($broadcastData['message']['channel'])->toHaveKey('name');
        expect($broadcastData['message']['channel'])->toHaveKey('type');
    });
});
