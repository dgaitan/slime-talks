<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;

/**
 * Message Service Interface
 *
 * Defines the contract for message business logic operations.
 * Abstracts business rules for message management.
 *
 * @package App\Services
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
interface MessageServiceInterface
{
    /**
     * Send a message to a channel.
     *
     * @param array<string, mixed> $data Message data
     * @param int $clientId Client ID
     * @return Message The created message
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function sendMessage(array $data, int $clientId): Message;

    /**
     * Get messages for a channel.
     *
     * @param string $channelUuid Channel UUID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If channel not found
     */
    public function getChannelMessages(string $channelUuid, int $clientId, int $limit = 10, ?string $startingAfter = null): array;

    /**
     * Get messages for a customer.
     *
     * @param string $customerUuid Customer UUID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If customer not found
     */
    public function getCustomerMessages(string $customerUuid, int $clientId, int $limit = 10, ?string $startingAfter = null): array;
}
