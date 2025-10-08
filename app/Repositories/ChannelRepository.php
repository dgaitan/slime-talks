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
 * with proper client isolation and customer relationships.
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
     *
     * @param array $data Channel data containing client_id, type, and name
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
     * Attach customers to a channel.
     *
     * Attaches a list of customers to the specified channel using the pivot table.
     *
     * @param Channel $channel The channel instance
     * @param array $customerIds Array of customer IDs to attach
     * @return void
     *
     * @example
     * $repository->attachCustomers($channel, [1, 2]);
     * // Attaches customers with IDs 1 and 2 to the channel
     */
    public function attachCustomers(Channel $channel, array $customerIds): void
    {
        $channel->customers()->attach($customerIds);
    }

    /**
     * Check if a general channel exists between a set of customers for a client.
     *
     * Determines if a 'general' type channel already exists that includes exactly
     * the given set of customer IDs for a specific client.
     *
     * @param array $customerIds Array of customer IDs
     * @param Client $client The client
     * @return bool True if a general channel exists, false otherwise
     *
     * @example
     * $exists = $repository->generalChannelExistsBetweenCustomers([1, 2], $client);
     * if ($exists) {
     *     // General channel exists between customers 1 and 2 for this client
     * }
     */
    public function generalChannelExistsBetweenCustomers(array $customerIds, Client $client): bool
    {
        // Sort customer IDs to ensure consistent comparison regardless of order
        sort($customerIds);
        $customerIdsJson = json_encode($customerIds);

        // Find all general channels for the client
        $generalChannels = Channel::where('client_id', $client->id)
            ->where('type', 'general')
            ->get();

        foreach ($generalChannels as $channel) {
            // Get customer IDs for the current channel and sort them
            $channelCustomerIds = $channel->customers->pluck('id')->toArray();
            sort($channelCustomerIds);
            $channelCustomerIdsJson = json_encode($channelCustomerIds);

            // Compare sorted customer ID arrays
            if ($customerIdsJson === $channelCustomerIdsJson) {
                return true;
            }
        }

        return false;
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