<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Repositories\MessageRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Message Service
 *
 * Handles business logic for message management.
 * Implements the MessageServiceInterface contract.
 *
 * @package App\Services
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class MessageService implements MessageServiceInterface
{
    /**
     * Create a new MessageService instance.
     *
     * @param MessageRepositoryInterface $messageRepository Message repository
     */
    public function __construct(
        private readonly MessageRepositoryInterface $messageRepository
    ) {}

    /**
     * Send a message to a channel.
     *
     * Validates that the sender is a participant in the channel
     * and that both channel and sender belong to the authenticated client.
     *
     * @param array<string, mixed> $data Message data
     * @param int $clientId Client ID
     * @return Message The created message
     * @throws ValidationException If validation fails
     */
    public function sendMessage(array $data, int $clientId): Message
    {
        try {
            // Find channel and validate it belongs to client
            $channel = $this->messageRepository->findChannelByUuidAndClient(
                $data['channel_uuid'],
                $clientId
            );

            if (!$channel) {
                Log::warning('Message send failed: Channel not found or does not belong to client', [
                    'channel_uuid' => $data['channel_uuid'],
                    'client_id' => $clientId,
                ]);

                throw ValidationException::withMessages([
                    'channel_uuid' => ['Channel does not exist or does not belong to your client.'],
                ]);
            }

            // Find sender and validate it belongs to client
            $sender = $this->messageRepository->findCustomerByUuidAndClient(
                $data['sender_uuid'],
                $clientId
            );

            if (!$sender) {
                Log::warning('Message send failed: Sender not found or does not belong to client', [
                    'sender_uuid' => $data['sender_uuid'],
                    'client_id' => $clientId,
                ]);

                throw ValidationException::withMessages([
                    'sender_uuid' => ['Sender does not exist or does not belong to your client.'],
                ]);
            }

            // Validate sender is participant in channel
            if (!$this->messageRepository->isCustomerInChannel($sender->id, $channel->id)) {
                Log::warning('Message send failed: Sender is not a participant in channel', [
                    'sender_id' => $sender->id,
                    'channel_id' => $channel->id,
                ]);

                throw ValidationException::withMessages([
                    'sender_uuid' => ['Sender is not a participant in this channel.'],
                ]);
            }

            // Create message
            $messageData = [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'client_id' => $clientId,
                'channel_id' => $channel->id,
                'sender_id' => $sender->id,
                'type' => $data['type'],
                'content' => $data['content'],
                'metadata' => $data['metadata'] ?? null,
            ];

            $message = $this->messageRepository->create($messageData);

            return $message;

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error sending message', [
                'error' => $e->getMessage(),
                'data' => $data,
                'client_id' => $clientId,
            ]);

            throw ValidationException::withMessages([
                'general' => ['An unexpected error occurred while sending the message.'],
            ]);
        }
    }
}
