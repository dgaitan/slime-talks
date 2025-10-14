<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Client;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Channel Repository Interface
 * 
 * Defines the contract for channel data access operations.
 * Provides methods for creating, retrieving, and managing channels
 * with proper client isolation and business logic.
 * 
 * @package App\Repositories
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
interface ChannelRepositoryInterface
{
    /**
     * Create a new channel.
     * 
     * Creates a new channel record in the database with the provided data.
     * The channel will be automatically associated with the client specified in the data.
     * 
     * @param array $data Channel data containing client_id, type, name, and customer IDs
     * @return Channel Created channel instance
     */
    public function create(array $data): Channel;

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
     */
    public function findByUuidAndClient(string $uuid, Client $client): Channel;

    /**
     * Check if a general channel exists between customers.
     * 
     * Verifies if a general channel already exists between the specified customers.
     * This prevents duplicate general channels between the same customers.
     * 
     * @param array $customerIds Array of customer IDs to check
     * @param Client $client Client instance to scope the search
     * @return bool True if general channel exists between these customers
     */
    public function generalChannelExistsBetweenCustomers(array $customerIds, Client $client): bool;

    /**
     * Attach customers to a channel.
     * 
     * Creates the many-to-many relationships between a channel and customers.
     * 
     * @param Channel $channel Channel instance
     * @param array $customerIds Array of customer IDs to attach
     * @return void
     */
    public function attachCustomers(Channel $channel, array $customerIds): void;

    /**
     * Get customers for a channel.
     * 
     * Retrieves all customers that belong to the specified channel.
     * 
     * @param Channel $channel Channel instance
     * @return Collection Collection of customers in the channel
     */
    public function getCustomers(Channel $channel): Collection;

    /**
     * Find existing custom channel with same name.
     * 
     * Searches for a custom channel with the same name.
     * Returns the existing channel if found, null otherwise.
     * 
     * @param string $name Channel name to search for
     * @param Client $client Client instance to scope the search
     * @return Channel|null Existing channel if found, null otherwise
     */
    public function findExistingCustomChannel(string $name, Client $client): ?Channel;

    /**
     * Get channels for a customer by email, grouped by recipient.
     *
     * Retrieves all channels where the specified customer participates,
     * grouped by the other participants (recipients). Results are ordered
     * by the latest message activity within each conversation.
     *
     * @param Client $client Client instance to get channels for
     * @param string $email Customer email to get channels for
     * @return array{data: array, total_count: int} Grouped channels data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found
     */
    public function getChannelsByEmail(Client $client, string $email): array;
}
