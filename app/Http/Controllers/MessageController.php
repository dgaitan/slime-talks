<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\MessageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Message Controller
 *
 * Handles HTTP requests for message management.
 * Provides endpoints for sending messages in channels.
 *
 * @package App\Http\Controllers
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class MessageController extends Controller
{
    /**
     * Create a new MessageController instance.
     *
     * @param MessageServiceInterface $messageService Message service
     */
    public function __construct(
        private readonly MessageServiceInterface $messageService
    ) {}

    /**
     * Send a message to a channel.
     *
     * Creates a new message in the specified channel.
     * Validates that the sender is a participant in the channel.
     *
     * @param CreateMessageRequest $request The validated request
     * @return JsonResponse The message response
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function store(CreateMessageRequest $request): JsonResponse
    {
        try {
            $client = auth('sanctum')->user();
            $message = $this->messageService->sendMessage($request->validated(), $client->id);

            return response()->json(new MessageResource($message), 201);

        } catch (ValidationException $e) {
            // Re-throw validation exceptions to return proper 422 responses
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to send message. Please try again.',
            ], 500);
        }
    }

    /**
     * Get messages for a channel.
     *
     * Retrieves paginated messages from a specific channel.
     * Messages are ordered by creation time (oldest first).
     *
     * @param string $channelUuid Channel UUID
     * @param Request $request The HTTP request
     * @return JsonResponse The messages response
     */
    public function getChannelMessages(string $channelUuid, Request $request): JsonResponse
    {
        try {
            $client = auth('sanctum')->user();
            
            $limit = (int) $request->get('limit', 10);
            $startingAfter = $request->get('starting_after');

            $result = $this->messageService->getChannelMessages(
                $channelUuid,
                $client->id,
                $limit,
                $startingAfter
            );

            return response()->json([
                'object' => 'list',
                'data' => MessageResource::collection($result['data']),
                'has_more' => $result['has_more'],
                'total_count' => $result['total_count'],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Channel not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve channel messages', [
                'error' => $e->getMessage(),
                'channel_uuid' => $channelUuid,
            ]);

            return response()->json([
                'error' => 'Failed to retrieve messages. Please try again.',
            ], 500);
        }
    }

    /**
     * Get messages for a customer.
     *
     * Retrieves paginated messages from all channels where the customer has sent messages.
     * Messages are ordered by creation time (newest first).
     *
     * @param string $customerUuid Customer UUID
     * @param Request $request The HTTP request
     * @return JsonResponse The messages response
     */
    public function getCustomerMessages(string $customerUuid, Request $request): JsonResponse
    {
        try {
            $client = auth('sanctum')->user();
            
            $limit = (int) $request->get('limit', 10);
            $startingAfter = $request->get('starting_after');

            $result = $this->messageService->getCustomerMessages(
                $customerUuid,
                $client->id,
                $limit,
                $startingAfter
            );

            return response()->json([
                'object' => 'list',
                'data' => MessageResource::collection($result['data']),
                'has_more' => $result['has_more'],
                'total_count' => $result['total_count'],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve customer messages', [
                'error' => $e->getMessage(),
                'customer_uuid' => $customerUuid,
            ]);

            return response()->json([
                'error' => 'Failed to retrieve messages. Please try again.',
            ], 500);
        }
    }
}
