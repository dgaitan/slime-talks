<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Message;

/**
 * Message Repository Interface
 *
 * Defines the contract for message data access operations.
 * Abstracts database interactions for message management.
 *
 * @package App\Repositories
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
interface MessageRepositoryInterface
{
    /**
     * Create a new message.
     *
     * @param array<string, mixed> $data Message data
     * @return Message The created message
     */
    public function create(array $data): Message;

    /**
     * Find a message by UUID and client.
     *
     * @param string $uuid Message UUID
     * @param int $clientId Client ID
     * @return Message|null The message or null if not found
     */
    public function findByUuidAndClient(string $uuid, int $clientId): ?Message;

    /**
     * Check if a customer is a participant in a channel.
     *
     * @param int $customerId Customer ID
     * @param int $channelId Channel ID
     * @return bool True if customer is in channel
     */
    public function isCustomerInChannel(int $customerId, int $channelId): bool;

    /**
     * Find a channel by UUID and client.
     *
     * @param string $uuid Channel UUID
     * @param int $clientId Client ID
     * @return \App\Models\Channel|null The channel or null if not found
     */
    public function findChannelByUuidAndClient(string $uuid, int $clientId): ?\App\Models\Channel;

    /**
     * Find a customer by UUID and client.
     *
     * @param string $uuid Customer UUID
     * @param int $clientId Client ID
     * @return \App\Models\Customer|null The customer or null if not found
     */
    public function findCustomerByUuidAndClient(string $uuid, int $clientId): ?\App\Models\Customer;
}
