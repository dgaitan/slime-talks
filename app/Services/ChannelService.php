<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use App\Repositories\ChannelRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Channel Service
 * 
 * Handles business logic for channel management operations.
 * Provides methods for creating channels with proper validation and client isolation.
 * 
 * @package App\Services
 * @author Laravel Slime Talks
 * @version 1.0.0
 * 
 * @example
 * $service = app(ChannelService::class);
 * $channel = $service->create($client, [
 *     'type' => 'general',
 *     'customer_uuids' => ['uuid1', 'uuid2']
 * ]);
 */
class ChannelService implements ChannelServiceInterface
{
    /**
     * Create a new ChannelService instance.
     * 
     * Injects the channel repository dependency for data access operations.
     * 
     * @param ChannelRepositoryInterface $channelRepository Repository for channel data operations
     */
    public function __construct(
        private ChannelRepositoryInterface $channelRepository
    ) {}

    /**
     * Create a new channel for a client.
     * 
     * Validates channel data, ensures customer validation,
     * and creates a new channel record with proper relationships.
     * 
     * @param Client $client The client creating the channel
     * @param array $data Channel data containing type and customer UUIDs
     * @return Channel Created channel instance
     * @throws ValidationException When validation fails
     * 
     * @example
     * $channel = $service->create($client, [
     *     'type' => 'general',
     *     'customer_uuids' => ['customer-uuid-1', 'customer-uuid-2']
     * ]);
     */
    public function create(Client $client, array $data): Channel
    {
        $this->validateChannelData($data);
        
        // Get customer IDs from UUIDs and validate they belong to the client
        $customerIds = $this->getAndValidateCustomers($client, $data['customer_uuids']);
        
        // For general channels, check if channel already exists between these customers
        if ($data['type'] === 'general') {
            $this->ensureNoDuplicateGeneralChannel($customerIds, $client);
        }
        
        // Create the channel
        $channel = $this->channelRepository->create([
            'client_id' => $client->id,
            'type' => $data['type'],
            'name' => $data['type'] === 'general' ? 'general' : $data['name'] ?? 'custom',
        ]);
        
        // Attach customers to the channel
        $this->channelRepository->attachCustomers($channel, $customerIds);
        
        return $channel;
    }

    /**
     * Validate channel data.
     * 
     * Performs basic validation on channel data including required fields
     * and type validation.
     * 
     * @param array $data Channel data to validate
     * @return void
     * @throws ValidationException When validation fails
     * 
     * @example
     * $this->validateChannelData([
     *     'type' => 'general',
     *     'customer_uuids' => ['uuid1', 'uuid2']
     * ]); // Throws ValidationException if invalid
     */
    private function validateChannelData(array $data): void
    {
        if (empty($data['type'])) {
            $validator = Validator::make([], []);
            $validator->errors()->add('type', 'Channel type is required');
            throw new ValidationException($validator);
        }
        
        if (!in_array($data['type'], ['general', 'custom'])) {
            $validator = Validator::make([], []);
            $validator->errors()->add('type', 'Channel type must be general or custom');
            throw new ValidationException($validator);
        }
        
        if (empty($data['customer_uuids']) || !is_array($data['customer_uuids'])) {
            $validator = Validator::make([], []);
            $validator->errors()->add('customer_uuids', 'Customer UUIDs are required and must be an array');
            throw new ValidationException($validator);
        }
    }

    /**
     * Get and validate customers for the channel.
     * 
     * Retrieves customer IDs from UUIDs and ensures they all belong to the client.
     * 
     * @param Client $client The client
     * @param array $customerUuids Array of customer UUIDs
     * @return array Array of customer IDs
     * @throws ValidationException When customers don't exist or don't belong to client
     * 
     * @example
     * $customerIds = $this->getAndValidateCustomers($client, ['uuid1', 'uuid2']);
     * // Returns [1, 2] if both customers exist and belong to the client
     */
    private function getAndValidateCustomers(Client $client, array $customerUuids): array
    {
        $customers = Customer::whereIn('uuid', $customerUuids)
            ->where('client_id', $client->id)
            ->get();
        
        if ($customers->count() !== count($customerUuids)) {
            $validator = Validator::make([], []);
            $validator->errors()->add('customer_uuids', 'One or more customers do not exist or do not belong to this client');
            throw new ValidationException($validator);
        }
        
        return $customers->pluck('id')->toArray();
    }

    /**
     * Ensure no duplicate general channel exists.
     * 
     * Checks if a general channel already exists between the specified customers.
     * 
     * @param array $customerIds Array of customer IDs
     * @param Client $client The client
     * @return void
     * @throws ValidationException When duplicate general channel exists
     * 
     * @example
     * $this->ensureNoDuplicateGeneralChannel([1, 2], $client);
     * // Throws ValidationException if general channel already exists between customers 1 and 2
     */
    private function ensureNoDuplicateGeneralChannel(array $customerIds, Client $client): void
    {
        if ($this->channelRepository->generalChannelExistsBetweenCustomers($customerIds, $client)) {
            $validator = Validator::make([], []);
            $validator->errors()->add('customer_uuids', 'A general channel already exists between these customers');
            throw new ValidationException($validator);
        }
    }
}
