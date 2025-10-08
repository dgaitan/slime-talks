<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Channel as ChannelModel;
use App\Models\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Typing Started Event
 *
 * Broadcasts when a user starts typing in a channel.
 * This event is broadcast to all other participants in the channel.
 *
 * @package App\Events
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class TypingStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Customer $customer The customer who started typing
     * @param Channel $channel The channel where typing started
     */
    public function __construct(
        public readonly Customer $customer,
        public readonly ChannelModel $channel
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("channel.{$this->channel->uuid}"),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'typing' => [
                'user' => [
                    'id' => $this->customer->uuid,
                    'name' => $this->customer->name,
                ],
                'channel' => [
                    'id' => $this->channel->uuid,
                    'name' => $this->channel->name,
                ],
                'started_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'typing.started';
    }
}