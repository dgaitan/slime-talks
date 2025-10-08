<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use App\Repositories\ChannelRepositoryInterface;
use Illuminate\Support\Facades\Log;
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
     * For custom channels, automatically creates a general channel if it doesn't exist.
     * 
     * @param Client $client The client creating the channel
     * @param array $data Channel data containing type and customer UUIDs
     * @return Channel Created channel instance
     * @throws ValidationException When validation fails
     * 
     * @example
     * $channel = $service->create($client, [
     *     'type' => 'custom',
     *     'name' => 'Project Discussion',
     *     'customer_uuids' => ['customer-uuid-1', 'customer-uuid-2']
     * ]);
     */
    public function create(Client $client, array $data): Channel
    {
        try {
            $this->validateChannelData($data);
            
            // Get customer IDs from UUIDs and validate they belong to the client
            $customerIds = $this->getAndValidateCustomers($client, $data['customer_uuids']);
            
            // For general channels, check if channel already exists between these customers
            if ($data['type'] === 'general') {
                $this->ensureNoDuplicateGeneralChannel($customerIds, $client);
            }
            
            // For custom channels, check if general channel exists and create it if not
            if ($data['type'] === 'custom') {
                // $this->ensureGeneralChannelExists($customerIds, $client);
                
                // Check if a custom channel with the same name already exists
                $existingChannel = $this->channelRepository->findExistingCustomChannel($data['name'], $client);
                if ($existingChannel) {
                    Log::info('Returning existing custom channel instead of creating duplicate', [
                        'client_id' => $client->id,
                        'client_uuid' => $client->uuid,
                        'channel_id' => $existingChannel->id,
                        'channel_uuid' => $existingChannel->uuid,
                        'channel_name' => $existingChannel->name,
                        'customer_count' => $existingChannel->customers->count(),
                    ]);
                    return $existingChannel;
                }
            }
            
            // Create the channel
            $channelData = [
                'client_id' => $client->id,
                'type' => $data['type'],
                'name' => $data['type'] === 'general' ? 'general' : $data['name'],
            ];
            
            $channel = $this->channelRepository->create($channelData);
            
            // Attach customers to the channel
            $this->channelRepository->attachCustomers($channel, $customerIds);
            
            return $channel;
            
        } catch (ValidationException $e) {
            Log::warning('Channel creation failed due to validation error', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'channel_type' => $data['type'] ?? 'unknown',
                'validation_errors' => $e->errors(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
            
        } catch (\Exception $e) {
            Log::error('Channel creation failed with unexpected error', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'channel_type' => $data['type'] ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
            Log::warning('Customer validation failed - not all customers found or belong to client', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'requested_uuids' => $customerUuids,
                'found_customers' => $customers->pluck('uuid')->toArray(),
                'missing_count' => count($customerUuids) - $customers->count(),
            ]);
            
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

    /**
     * Ensure general channel exists for custom channel creation.
     * 
     * When creating a custom channel, automatically creates a general channel
     * between the same customers if it doesn't already exist.
     * 
     * @param array $customerIds Array of customer IDs
     * @param Client $client The client
     * @return void
     * 
     * @example
     * $this->ensureGeneralChannelExists([1, 2], $client);
     * // Creates general channel between customers 1 and 2 if it doesn't exist
     */
    private function ensureGeneralChannelExists(array $customerIds, Client $client): void
    {
        // Check if general channel already exists between these customers
        $generalChannelExists = $this->channelRepository->generalChannelExistsBetweenCustomers($customerIds, $client);
        
        if (!$generalChannelExists) {
            // Create general channel
            $generalChannel = $this->channelRepository->create([
                'client_id' => $client->id,
                'type' => 'general',
                'name' => 'general',
            ]);
            
            // Attach customers to the general channel
            $this->channelRepository->attachCustomers($generalChannel, $customerIds);
        }
    }

    /**
     * Get channel by UUID for a specific client.
     *
     * Retrieves a channel by UUID, ensuring it belongs to the specified client.
     * This provides client isolation - clients can only access their own channels.
     *
     * @param Client $client The client requesting the channel
     * @param string $uuid Channel UUID to retrieve
     * @return Channel Channel instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When channel not found or doesn't belong to client
     *
     * @example
     * $channel = $service->getByUuid($client, 'channel-uuid-here');
     * echo $channel->name; // "general"
     */
    public function getByUuid(Client $client, string $uuid): Channel
    {
        return $this->channelRepository->findByUuidAndClient($uuid, $client);
    }

    /**
     * List channels for a client with pagination.
     *
     * Retrieves a paginated list of channels belonging to the specified client.
     * Supports cursor-based pagination for efficient handling of large datasets.
     *
     * @param Client $client The client requesting channels
     * @param int $limit Number of channels per page (default: 10)
     * @param string|null $startingAfter UUID to start after for cursor pagination
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int} Paginated results
     *
     * @example
     * $result = $service->list($client, 20, 'previous-channel-uuid');
     * $channels = $result['data']; // Collection of channels
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of channels for this client
     */
    public function list(Client $client, int $limit = 10, ?string $startingAfter = null): array
    {
        return $this->channelRepository->paginateByClient($client, $limit, $startingAfter);
    }

    /**
     * Get channels for a specific customer.
     *
     * Retrieves all channels where the specified customer participates.
     * Only returns channels belonging to the specified client.
     *
     * @param Client $client The client requesting the channels
     * @param string $customerUuid Customer UUID to get channels for
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int} Customer's channels
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found or doesn't belong to client
     *
     * @example
     * $result = $service->getChannelsForCustomer($client, 'customer-uuid-here');
     * $channels = $result['data']; // Collection of channels
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of channels for this customer
     */
    public function getChannelsForCustomer(Client $client, string $customerUuid): array
    {
        return $this->channelRepository->getChannelsForCustomer($client, $customerUuid);
    }
}
