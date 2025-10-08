<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Message Resource
 *
 * Transforms Message model data into consistent API responses.
 * Follows Stripe-inspired API patterns for consistent formatting.
 *
 * @package App\Http\Resources
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Formats the message data for API responses following Stripe patterns.
     * Includes all necessary message information with proper timestamps and sender details.
     *
     * @param Request $request The HTTP request
     * @return array<string, mixed> Formatted message data
     *
     * @example
     * // Response format:
     * {
     *     "object": "message",
     *     "id": "message_uuid",
     *     "channel_id": "channel_uuid",
     *     "sender_id": "customer_uuid",
     *     "type": "text",
     *     "content": "Hello world!",
     *     "metadata": {"priority": "high"},
     *     "created": 1640995200,
     *     "livemode": false
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'object' => 'message',
            'id' => $this->uuid,
            'channel_id' => $this->channel->uuid,
            'sender_id' => $this->sender->uuid,
            'type' => $this->type,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'created' => $this->created_at?->timestamp,
            'livemode' => false, // TODO: Implement livemode logic
        ];
    }
}
