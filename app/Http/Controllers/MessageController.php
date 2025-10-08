<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\MessageServiceInterface;
use Illuminate\Http\JsonResponse;
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
}
