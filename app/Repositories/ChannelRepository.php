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

    /**
     * Paginate channels for a client.
     *
     * Retrieves a paginated list of channels belonging to the specified client.
     * Supports cursor-based pagination for efficient handling of large datasets.
     * Results are ordered by creation date in descending order (newest first).
     *
     * @param Client $client Client instance to get channels for
     * @param int $limit Number of channels per page
     * @param string|null $startingAfter UUID to start after for cursor pagination
     * @return array{data: Collection, has_more: bool, total_count: int} Paginated results
     *
     * @example
     * $result = $repository->paginateByClient($client, 20, 'previous-channel-uuid');
     * $channels = $result['data']; // Collection of channels
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of channels for this client
     */
    public function paginateByClient(Client $client, int $limit, ?string $startingAfter = null): array
    {
        $query = Channel::query()
            ->where('client_id', $client->id)
            ->with('customers')
            ->orderBy('updated_at', 'desc') // Order by latest activity (most recent first)
            ->orderBy('id', 'desc'); // Secondary sort for ties

        if ($startingAfter) {
            $startingChannel = Channel::where('uuid', $startingAfter)->first();
            if ($startingChannel) {
                $query->where('updated_at', '<=', $startingChannel->updated_at)
                    ->where('id', '<', $startingChannel->id);
            }
        }

        $channels = $query->limit($limit + 1)->get();
        $hasMore = $channels->count() > $limit;
        
        if ($hasMore) {
            $channels->pop();
        }

        return [
            'data' => $channels,
            'has_more' => $hasMore,
            'total_count' => Channel::where('client_id', $client->id)->count(),
        ];
    }

    /**
     * Get channels for a specific customer.
     *
     * Retrieves all channels where the specified customer participates.
     * Only returns channels belonging to the specified client.
     * Results are ordered by latest activity (updated_at) in descending order.
     *
     * @param Client $client Client instance to get channels for
     * @param string $customerUuid Customer UUID to get channels for
     * @return array{data: Collection, has_more: bool, total_count: int} Customer's channels
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found or doesn't belong to client
     *
     * @example
     * $result = $repository->getChannelsForCustomer($client, 'customer-uuid-here');
     * $channels = $result['data']; // Collection of channels
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of channels for this customer
     */
    public function getChannelsForCustomer(Client $client, string $customerUuid): array
    {
        // First, find the customer and ensure it belongs to the client
        $customer = \App\Models\Customer::where('uuid', $customerUuid)
            ->where('client_id', $client->id)
            ->firstOrFail();

        // Get channels where this customer participates, ordered by latest activity
        $channels = Channel::where('client_id', $client->id)
            ->whereHas('customers', function ($query) use ($customer) {
                $query->where('customers.id', $customer->id);
            })
            ->orderBy('updated_at', 'desc') // Order by latest activity
            ->orderBy('id', 'desc') // Secondary sort for ties
            ->get();

        return [
            'data' => $channels,
            'has_more' => false, // No pagination for customer channels
            'total_count' => $channels->count(),
        ];
    }

    /**
     * Find existing custom channel with same name.
     *
     * Searches for a custom channel with the same name.
     * Returns the existing channel if found, null otherwise.
     *
     * @param string $name Channel name to search for
     * @param Client $client Client instance to scope the search
     * @return Channel|null Existing channel if found, null otherwise
     *
     * @example
     * $existingChannel = $repository->findExistingCustomChannel('Project Discussion', $client);
     * if ($existingChannel) {
     *     // Channel with same name already exists
     * }
     */
    public function findExistingCustomChannel(string $name, Client $client): ?Channel
    {
        // Find the first custom channel for the client with the same name
        return Channel::where('client_id', $client->id)
            ->where('type', 'custom')
            ->where('name', $name)
            ->first();
    }

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
     *
     * @example
     * $result = $repository->getChannelsByEmail($client, 'john@example.com');
     * $conversations = $result['data']['conversations'];
     * $totalCount = $result['total_count'];
     */
    public function getChannelsByEmail(Client $client, string $email): array
    {
        // First, find the customer by email and ensure it belongs to the client
        $customer = \App\Models\Customer::where('email', $email)
            ->where('client_id', $client->id)
            ->firstOrFail();

        // Get all channels where this customer participates
        $channels = Channel::where('client_id', $client->id)
            ->whereHas('customers', function ($query) use ($customer) {
                $query->where('customers.id', $customer->id);
            })
            ->with(['customers' => function ($query) use ($customer) {
                $query->where('customers.id', '!=', $customer->id); // Exclude the requesting customer
            }])
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Group channels by recipient
        $conversations = [];
        $recipientMap = [];

        foreach ($channels as $channel) {
            foreach ($channel->customers as $recipient) {
                $recipientKey = $recipient->id;

                if (!isset($recipientMap[$recipientKey])) {
                    $recipientMap[$recipientKey] = [
                        'recipient' => [
                            'object' => 'customer',
                            'id' => $recipient->uuid,
                            'name' => $recipient->name,
                            'email' => $recipient->email,
                        ],
                        'channels' => [],
                        'latest_message_at' => $channel->updated_at->timestamp,
                    ];
                }

                $recipientMap[$recipientKey]['channels'][] = [
                    'object' => 'channel',
                    'id' => $channel->uuid,
                    'type' => $channel->type,
                    'name' => $channel->name,
                    'updated_at' => $channel->updated_at->timestamp,
                ];

                // Update latest message time if this channel is more recent
                if ($channel->updated_at->timestamp > $recipientMap[$recipientKey]['latest_message_at']) {
                    $recipientMap[$recipientKey]['latest_message_at'] = $channel->updated_at->timestamp;
                }
            }
        }

        // Convert to array and sort by latest message time
        $conversations = array_values($recipientMap);
        usort($conversations, function ($a, $b) {
            return $b['latest_message_at'] <=> $a['latest_message_at'];
        });

        return [
            'data' => [
                'conversations' => $conversations,
            ],
            'total_count' => count($conversations),
        ];
    }
}