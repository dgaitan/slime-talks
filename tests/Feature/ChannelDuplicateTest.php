<?php

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use App\Services\ChannelService;

beforeEach(function () {
    $this->client = Client::factory()->create([
        'name' => 'Test Client',
        'domain' => 'test.com',
        'public_key' => 'pk_test_1234567890',
    ]);

    $this->token = $this->client->createToken('test-token')->plainTextToken;

    // Create test customers
    $this->customer1 = Customer::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'Customer One',
        'email' => 'customer1@test.com',
    ]);

    $this->customer2 = Customer::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'Customer Two',
        'email' => 'customer2@test.com',
    ]);

    $this->channelService = app(ChannelService::class);
});

describe('Channel Duplicate Prevention', function () {
    it('returns existing custom channel when creating duplicate with same name and customers', function () {
        // Create initial custom channel
        $initialChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        expect($initialChannel)->toBeInstanceOf(Channel::class);
        expect($initialChannel->name)->toBe('Project Discussion');
        expect($initialChannel->type)->toBe('custom');

        // Try to create the same custom channel again
        $duplicateChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        // Should return the same channel
        expect($duplicateChannel->id)->toBe($initialChannel->id);
        expect($duplicateChannel->uuid)->toBe($initialChannel->uuid);
        expect($duplicateChannel->name)->toBe('Project Discussion');
        expect($duplicateChannel->type)->toBe('custom');
    });

    it('returns existing custom channel even when customers are different', function () {
        $customer3 = Customer::factory()->create([
            'client_id' => $this->client->id,
            'name' => 'Customer Three',
            'email' => 'customer3@test.com',
        ]);

        // Create initial custom channel
        $initialChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        // Try to create channel with same name but different customers
        $sameChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $customer3->uuid],
        ]);

        // Should return the existing channel (searching by name only)
        expect($sameChannel->id)->toBe($initialChannel->id);
        expect($sameChannel->uuid)->toBe($initialChannel->uuid);
        expect($sameChannel->name)->toBe('Project Discussion');
        expect($sameChannel->type)->toBe('custom');
    });

    it('creates new custom channel when name is different', function () {
        // Create initial custom channel
        $initialChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        // Create channel with different name but same customers
        $differentChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Team Meeting',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        // Should create a new channel
        expect($differentChannel->id)->not->toBe($initialChannel->id);
        expect($differentChannel->uuid)->not->toBe($initialChannel->uuid);
        expect($differentChannel->name)->toBe('Team Meeting');
        expect($differentChannel->type)->toBe('custom');
    });

    it('returns existing channel by name regardless of customer list', function () {
        // Create initial custom channel
        $initialChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        // Try to create the same channel with customers in different order
        $duplicateChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer2->uuid, $this->customer1->uuid], // Different order
        ]);

        // Should return the same channel (only checks name)
        expect($duplicateChannel->id)->toBe($initialChannel->id);
        expect($duplicateChannel->uuid)->toBe($initialChannel->uuid);
    });

    it('still prevents duplicate general channels', function () {
        // Create initial general channel
        $initialChannel = $this->channelService->create($this->client, [
            'type' => 'general',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        expect($initialChannel)->toBeInstanceOf(Channel::class);
        expect($initialChannel->type)->toBe('general');

        // Try to create duplicate general channel - should throw exception
        expect(function () {
            $this->channelService->create($this->client, [
                'type' => 'general',
                'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
            ]);
        })->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('creates new custom channel for different client even with same name and customers', function () {
        // Create another client
        $otherClient = Client::factory()->create([
            'name' => 'Other Client',
            'domain' => 'other.com',
            'public_key' => 'pk_other_1234567890',
        ]);

        // Create customers for the other client
        $otherCustomer1 = Customer::factory()->create([
            'client_id' => $otherClient->id,
            'name' => 'Other Customer One',
            'email' => 'other1@test.com',
        ]);

        $otherCustomer2 = Customer::factory()->create([
            'client_id' => $otherClient->id,
            'name' => 'Other Customer Two',
            'email' => 'other2@test.com',
        ]);

        // Create custom channel for first client
        $firstChannel = $this->channelService->create($this->client, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$this->customer1->uuid, $this->customer2->uuid],
        ]);

        // Create custom channel for other client with same name
        $secondChannel = $this->channelService->create($otherClient, [
            'type' => 'custom',
            'name' => 'Project Discussion',
            'customer_uuids' => [$otherCustomer1->uuid, $otherCustomer2->uuid],
        ]);

        // Should create separate channels
        expect($secondChannel->id)->not->toBe($firstChannel->id);
        expect($secondChannel->uuid)->not->toBe($firstChannel->uuid);
        expect($secondChannel->client_id)->toBe($otherClient->id);
        expect($firstChannel->client_id)->toBe($this->client->id);
    });
});
