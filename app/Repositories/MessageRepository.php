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
}
