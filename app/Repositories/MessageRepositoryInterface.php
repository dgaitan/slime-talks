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

    /**
     * Get messages for a channel with pagination.
     *
     * @param int $channelId Channel ID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     */
    public function getMessagesForChannel(int $channelId, int $clientId, int $limit = 10, ?string $startingAfter = null): array;

    /**
     * Get messages for a customer with pagination.
     *
     * @param int $customerId Customer ID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     */
    public function getMessagesForCustomer(int $customerId, int $clientId, int $limit = 10, ?string $startingAfter = null): array;

    /**
     * Find a customer by email and client.
     *
     * @param string $email Customer email
     * @param int $clientId Client ID
     * @return \App\Models\Customer|null The customer or null if not found
     */
    public function findCustomerByEmailAndClient(string $email, int $clientId): ?\App\Models\Customer;

    /**
     * Get messages between two customers with pagination.
     *
     * @param int $customer1Id First customer ID
     * @param int $customer2Id Second customer ID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     */
    public function getMessagesBetweenCustomers(int $customer1Id, int $customer2Id, int $clientId, int $limit = 10, ?string $startingAfter = null): array;
}
