<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

/**
 * Channel Repository
 * 
 * Handles data access operations for channel management.
 * Provides methods for creating, retrieving, and managing channels
 * with proper client isolation and business logic.
 * 
 * @package App\Repositories
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class ChannelRepository implements ChannelRepositoryInterface
{
    /**
     * Create a new channel.
     * 
     * Creates a new channel record in the database with the provided data.
     * The channel will be automatically associated with the client specified in the data.
     * 
     * @param array $data Channel data containing client_id, type, name, and customer IDs
     * @return Channel Created channel instance
     * 
     * @example
     * $channel = $repository->create([
     *     'client_id' => 1,
     *     'type' => 'general',
     *     'name' => 'general'
     * ]);
     */
    public function create(array $data): Channel
    {
        return Channel::create($data);
    }

    /**
     * Find channel by UUID and client.
     * 
     * Retrieves a channel by UUID, ensuring it belongs to the specified client.
     * This provides client isolation - clients can only access their own channels.
     * 
     * @param string $uuid Channel UUID to find
     * @param Client $client Client instance to scope the search
     * @return Channel Channel instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When channel not found or doesn't belong to client
     * 
     * @example
     * $channel = $repository->findByUuidAndClient('channel-uuid-here', $client);
     * echo $channel->name; // "general"
     */
    public function findByUuidAndClient(string $uuid, Client $client): Channel
    {
        return Channel::where('uuid', $uuid)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    /**
     * Check if a general channel exists between customers.
     * 
     * Verifies if a general channel already exists between the specified customers.
     * This prevents duplicate general channels between the same customers.
     * 
     * @param array $customerIds Array of customer IDs to check
     * @param Client $client Client instance to scope the search
     * @return bool True if general channel exists between these customers
     * 
     * @example
     * $exists = $repository->generalChannelExistsBetweenCustomers([1, 2], $client);
     * if ($exists) {
     *     // General channel already exists between these customers
     * }
     */
    public function generalChannelExistsBetweenCustomers(array $customerIds, Client $client): bool
    {
        // Get all general channels for this client
        $generalChannels = Channel::where('client_id', $client->id)
            ->where('type', 'general')
            ->get();

        foreach ($generalChannels as $channel) {
            $channelCustomerIds = $channel->customers()->pluck('customers.id')->toArray();
            
            // Check if the customer sets match (order doesn't matter)
            if (count($customerIds) === count($channelCustomerIds) && 
                empty(array_diff($customerIds, $channelCustomerIds))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attach customers to a channel.
     * 
     * Creates the many-to-many relationships between a channel and customers.
     * 
     * @param Channel $channel Channel instance
     * @param array $customerIds Array of customer IDs to attach
     * @return void
     * 
     * @example
     * $repository->attachCustomers($channel, [1, 2, 3]);
     */
    public function attachCustomers(Channel $channel, array $customerIds): void
    {
        $channel->customers()->attach($customerIds);
    }

    /**
     * Get customers for a channel.
     * 
     * Retrieves all customers that belong to the specified channel.
     * 
     * @param Channel $channel Channel instance
     * @return Collection Collection of customers in the channel
     * 
     * @example
     * $customers = $repository->getCustomers($channel);
     * echo $customers->count(); // Number of customers in the channel
     */
    public function getCustomers(Channel $channel): Collection
    {
        return $channel->customers;
    }
}
