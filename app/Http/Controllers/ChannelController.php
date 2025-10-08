<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateChannelRequest;
use App\Http\Resources\ChannelResource;
use App\Services\ChannelServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * Channel Controller
 * 
 * Handles HTTP requests for channel management operations.
 * Provides RESTful endpoints for creating and managing channels.
 * All operations are scoped to the authenticated client.
 * 
 * @package App\Http\Controllers
 * @author Laravel Slime Talks
 * @version 1.0.0
 * 
 * @example
 * // Create a general channel
 * POST /api/v1/channels
 * {
 *     "type": "general",
 *     "customer_uuids": ["customer-uuid-1", "customer-uuid-2"]
 * }
 */
class ChannelController extends Controller
{
    /**
     * Create a new ChannelController instance.
     * 
     * Injects the channel service dependency for handling business logic.
     * 
     * @param ChannelServiceInterface $channelService Service for channel operations
     */
    public function __construct(
        private ChannelServiceInterface $channelService
    ) {}

    /**
     * Store a newly created channel in storage.
     * 
     * Creates a new channel for the authenticated client.
     * Validates the request data and ensures proper customer validation.
     * 
     * @param CreateChannelRequest $request Validated request containing channel data
     * @return JsonResponse JSON response with created channel data
     * 
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Illuminate\Auth\AuthenticationException When client is not authenticated
     * 
     * @example
     * POST /api/v1/channels
     * {
     *     "type": "general",
     *     "customer_uuids": ["customer-uuid-1", "customer-uuid-2"]
     * }
     * 
     * Response (201):
     * {
     *     "object": "channel",
     *     "id": "channel_uuid",
     *     "type": "general",
     *     "name": "general",
     *     "created": 1640995200
     * }
     */
    public function store(CreateChannelRequest $request): JsonResponse
    {
        $channel = $this->channelService->create(
            auth('sanctum')->user(), // Client from middleware
            $request->validated()
        );

        return response()->json(new ChannelResource($channel), 201);
    }
}
