<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Database\Eloquent\Model;

/**
 * Message Repository
 *
 * Handles all database operations for message management.
 * Implements the MessageRepositoryInterface contract.
 *
 * @package App\Repositories
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class MessageRepository implements MessageRepositoryInterface
{
    /**
     * Create a new message.
     *
     * @param array<string, mixed> $data Message data
     * @return Message The created message
     */
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    /**
     * Find a message by UUID and client.
     *
     * @param string $uuid Message UUID
     * @param int $clientId Client ID
     * @return Message|null The message or null if not found
     */
    public function findByUuidAndClient(string $uuid, int $clientId): ?Message
    {
        return Message::where('uuid', $uuid)
            ->where('client_id', $clientId)
            ->first();
    }

    /**
     * Check if a customer is a participant in a channel.
     *
     * @param int $customerId Customer ID
     * @param int $channelId Channel ID
     * @return bool True if customer is in channel
     */
    public function isCustomerInChannel(int $customerId, int $channelId): bool
    {
        return \DB::table('channel_customer')
            ->where('customer_id', $customerId)
            ->where('channel_id', $channelId)
            ->exists();
    }

    /**
     * Find a channel by UUID and client.
     *
     * @param string $uuid Channel UUID
     * @param int $clientId Client ID
     * @return Channel|null The channel or null if not found
     */
    public function findChannelByUuidAndClient(string $uuid, int $clientId): ?Channel
    {
        return Channel::where('uuid', $uuid)
            ->where('client_id', $clientId)
            ->first();
    }

    /**
     * Find a customer by UUID and client.
     *
     * @param string $uuid Customer UUID
     * @param int $clientId Client ID
     * @return Customer|null The customer or null if not found
     */
    public function findCustomerByUuidAndClient(string $uuid, int $clientId): ?Customer
    {
        return Customer::where('uuid', $uuid)
            ->where('client_id', $clientId)
            ->first();
    }

    /**
     * Get messages for a channel with pagination.
     *
     * @param int $channelId Channel ID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     */
    public function getMessagesForChannel(int $channelId, int $clientId, int $limit = 10, ?string $startingAfter = null): array
    {
        $query = Message::where('channel_id', $channelId)
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($startingAfter) {
            $startingMessage = Message::where('uuid', $startingAfter)->first();
            if ($startingMessage) {
                $query->where(function ($q) use ($startingMessage) {
                    $q->where('created_at', '>', $startingMessage->created_at)
                        ->orWhere(function ($subQ) use ($startingMessage) {
                            $subQ->where('created_at', $startingMessage->created_at)
                                ->where('id', '>', $startingMessage->id);
                        });
                });
            }
        }

        $messages = $query->limit($limit + 1)->get();
        $hasMore = $messages->count() > $limit;
        
        if ($hasMore) {
            $messages->pop();
        }

        return [
            'data' => $messages,
            'has_more' => $hasMore,
            'total_count' => Message::where('channel_id', $channelId)
                ->where('client_id', $clientId)
                ->count(),
        ];
    }

    /**
     * Get messages for a customer with pagination.
     *
     * @param int $customerId Customer ID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     */
    public function getMessagesForCustomer(int $customerId, int $clientId, int $limit = 10, ?string $startingAfter = null): array
    {
        $query = Message::where('sender_id', $customerId)
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($startingAfter) {
            $startingMessage = Message::where('uuid', $startingAfter)->first();
            if ($startingMessage) {
                $query->where(function ($q) use ($startingMessage) {
                    $q->where('created_at', '<', $startingMessage->created_at)
                        ->orWhere(function ($subQ) use ($startingMessage) {
                            $subQ->where('created_at', $startingMessage->created_at)
                                ->where('id', '<', $startingMessage->id);
                        });
                });
            }
        }

        $messages = $query->limit($limit + 1)->get();
        $hasMore = $messages->count() > $limit;
        
        if ($hasMore) {
            $messages->pop();
        }

        return [
            'data' => $messages,
            'has_more' => $hasMore,
            'total_count' => Message::where('sender_id', $customerId)
                ->where('client_id', $clientId)
                ->count(),
        ];
    }

    /**
     * Find a customer by email and client.
     *
     * @param string $email Customer email
     * @param int $clientId Client ID
     * @return \App\Models\Customer|null The customer or null if not found
     */
    public function findCustomerByEmailAndClient(string $email, int $clientId): ?\App\Models\Customer
    {
        return \App\Models\Customer::where('email', $email)
            ->where('client_id', $clientId)
            ->first();
    }

    /**
     * Get messages between two customers with pagination.
     *
     * Retrieves all messages between two customers across all channels
     * where they both participate. Messages are ordered by creation time
     * (newest first) and include proper pagination support.
     *
     * @param int $customer1Id First customer ID
     * @param int $customer2Id Second customer ID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     *
     * @example
     * $result = $repository->getMessagesBetweenCustomers(1, 2, 1, 10, null);
     * $messages = $result['data']; // Collection of messages
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of messages between customers
     */
    public function getMessagesBetweenCustomers(int $customer1Id, int $customer2Id, int $clientId, int $limit = 10, ?string $startingAfter = null): array
    {
        // Get all channels where both customers participate
        $channelIds = \App\Models\Channel::where('client_id', $clientId)
            ->whereHas('customers', function ($query) use ($customer1Id) {
                $query->where('customers.id', $customer1Id);
            })
            ->whereHas('customers', function ($query) use ($customer2Id) {
                $query->where('customers.id', $customer2Id);
            })
            ->pluck('id')
            ->toArray();

        if (empty($channelIds)) {
            return [
                'data' => collect([]),
                'has_more' => false,
                'total_count' => 0,
            ];
        }

        // Get messages from all channels where both customers participate
        $query = Message::where('client_id', $clientId)
            ->whereIn('channel_id', $channelIds)
            ->whereIn('sender_id', [$customer1Id, $customer2Id]) // Only messages from either customer
            ->with(['channel', 'sender'])
            ->orderBy('created_at', 'asc'); // Newest first

        if ($startingAfter) {
            $startingMessage = Message::where('uuid', $startingAfter)->first();
            if ($startingMessage) {
                $query->where('created_at', '<', $startingMessage->created_at);
            }
        }

        $messages = $query->limit($limit + 1)->get();
        $hasMore = $messages->count() > $limit;
        
        if ($hasMore) {
            $messages->pop();
        }

        return [
            'data' => $messages,
            'has_more' => $hasMore,
            'total_count' => Message::where('client_id', $clientId)
                ->whereIn('channel_id', $channelIds)
                ->whereIn('sender_id', [$customer1Id, $customer2Id])
                ->count(),
        ];
    }
}
