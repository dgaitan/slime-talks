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
}
