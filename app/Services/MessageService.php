<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\MessageSent;
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
     * @param ChannelServiceInterface $channelService Channel service
     */
    public function __construct(
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly ChannelServiceInterface $channelService
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

            // Update channel's updated_at timestamp to reflect latest activity
            $channel->touch();

            // Broadcast the message to channel participants
            broadcast(new MessageSent($message));

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

    /**
     * Get messages for a channel.
     *
     * Validates that the channel belongs to the authenticated client
     * and returns paginated messages ordered by creation time.
     *
     * @param string $channelUuid Channel UUID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If channel not found
     */
    public function getChannelMessages(string $channelUuid, int $clientId, int $limit = 10, ?string $startingAfter = null): array
    {
        try {
            // Find channel and validate it belongs to client
            $channel = $this->messageRepository->findChannelByUuidAndClient($channelUuid, $clientId);

            if (!$channel) {
                Log::warning('Channel messages retrieval failed: Channel not found or does not belong to client', [
                    'channel_uuid' => $channelUuid,
                    'client_id' => $clientId,
                ]);

                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Channel not found');
            }

            // Get messages for the channel
            return $this->messageRepository->getMessagesForChannel($channel->id, $clientId, $limit, $startingAfter);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error retrieving channel messages', [
                'error' => $e->getMessage(),
                'channel_uuid' => $channelUuid,
                'client_id' => $clientId,
            ]);

            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Channel not found');
        }
    }

    /**
     * Get messages for a customer.
     *
     * Validates that the customer belongs to the authenticated client
     * and returns paginated messages ordered by creation time (newest first).
     *
     * @param string $customerUuid Customer UUID
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If customer not found
     */
    public function getCustomerMessages(string $customerUuid, int $clientId, int $limit = 10, ?string $startingAfter = null): array
    {
        try {
            // Find customer and validate it belongs to client
            $customer = $this->messageRepository->findCustomerByUuidAndClient($customerUuid, $clientId);

            if (!$customer) {
                Log::warning('Customer messages retrieval failed: Customer not found or does not belong to client', [
                    'customer_uuid' => $customerUuid,
                    'client_id' => $clientId,
                ]);

                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Customer not found');
            }

            // Get messages for the customer
            return $this->messageRepository->getMessagesForCustomer($customer->id, $clientId, $limit, $startingAfter);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error retrieving customer messages', [
                'error' => $e->getMessage(),
                'customer_uuid' => $customerUuid,
                'client_id' => $clientId,
            ]);

            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Customer not found');
        }
    }

    /**
     * Get messages between two customers.
     *
     * Validates that both customers belong to the authenticated client
     * and returns paginated messages across all channels where they both participate.
     *
     * @param string $email1 First customer email
     * @param string $email2 Second customer email
     * @param int $clientId Client ID
     * @param int $limit Number of messages per page
     * @param string|null $startingAfter Message UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If customers not found
     */
    public function getMessagesBetweenCustomers(string $email1, string $email2, int $clientId, int $limit = 10, ?string $startingAfter = null): array
    {
        try {
            // Find both customers and validate they belong to client
            $customer1 = $this->messageRepository->findCustomerByEmailAndClient($email1, $clientId);
            $customer2 = $this->messageRepository->findCustomerByEmailAndClient($email2, $clientId);

            if (!$customer1 || !$customer2) {
                Log::warning('Messages between customers failed: One or both customers not found or do not belong to client', [
                    'email1' => $email1,
                    'email2' => $email2,
                    'client_id' => $clientId,
                ]);

                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('One or both customers not found');
            }

            // Get messages between the two customers
            return $this->messageRepository->getMessagesBetweenCustomers($customer1->id, $customer2->id, $clientId, $limit, $startingAfter);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error retrieving messages between customers', [
                'error' => $e->getMessage(),
                'email1' => $email1,
                'email2' => $email2,
                'client_id' => $clientId,
            ]);

            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('One or both customers not found');
        }
    }

    /**
     * Send a message to a customer (uses general channel between sender and recipient).
     *
     * Creates or finds the general channel between the sender and recipient,
     * then sends the message to that channel. This is useful for customer-centric
     * messaging interfaces where you want to send messages directly to customers.
     *
     * @param array<string, mixed> $data Message data with sender_email, recipient_email, etc.
     * @param int $clientId Client ID
     * @return Message The created message
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If customers not found
     */
    public function sendToCustomer(array $data, int $clientId): Message
    {
        try {
            // Find both customers and validate they belong to client
            $sender = $this->messageRepository->findCustomerByEmailAndClient($data['sender_email'], $clientId);
            $recipient = $this->messageRepository->findCustomerByEmailAndClient($data['recipient_email'], $clientId);

            if (!$sender || !$recipient) {
                Log::warning('Send to customer failed: One or both customers not found or do not belong to client', [
                    'sender_email' => $data['sender_email'],
                    'recipient_email' => $data['recipient_email'],
                    'client_id' => $clientId,
                ]);

                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('One or both customers not found');
            }

            // Create or find the general channel between the two customers
            $client = \App\Models\Client::find($clientId);
            $channel = $this->channelService->create($client, [
                'type' => 'general',
                'customer_uuids' => [$sender->uuid, $recipient->uuid],
            ]);

            // Send the message to the general channel
            return $this->sendMessage([
                'channel_uuid' => $channel->uuid,
                'sender_uuid' => $sender->uuid,
                'type' => $data['type'],
                'content' => $data['content'],
                'metadata' => $data['metadata'] ?? null,
            ], $clientId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error sending message to customer', [
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
