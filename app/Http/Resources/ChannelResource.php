<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Channel Resource
 * 
 * Transforms Channel model data into consistent API responses.
 * Follows Stripe-inspired API patterns for consistent formatting.
 * 
 * @package App\Http\Resources
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class ChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * Formats the channel data for API responses following Stripe patterns.
     * Includes all necessary channel information with proper timestamps.
     * 
     * @param Request $request The HTTP request
     * @return array<string, mixed> Formatted channel data
     * 
     * @example
     * // Response format:
     * {
     *     "object": "channel",
     *     "id": "channel_uuid",
     *     "type": "general",
     *     "name": "general",
     *     "created": 1640995200,
     *     "livemode": false
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'object' => 'channel',
            'id' => $this->uuid,
            'type' => $this->type,
            'name' => $this->name,
            'created' => $this->created_at?->timestamp,
            'livemode' => false, // TODO: Implement livemode logic
        ];
    }
}
