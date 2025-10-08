<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Channel as ChannelModel;
use App\Models\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Left Channel Event
 *
 * Broadcasts when a user leaves a channel.
 * This event is broadcast to all remaining participants in the channel.
 *
 * @package App\Events
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class UserLeftChannel implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Customer $customer The customer who left
     * @param Channel $channel The channel that was left
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
            new PresenceChannel("presence.channel.{$this->channel->uuid}"),
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
            'user' => [
                'id' => $this->customer->uuid,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'left_at' => now()->toISOString(),
            ],
            'channel' => [
                'id' => $this->channel->uuid,
                'name' => $this->channel->name,
                'type' => $this->channel->type,
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
        return 'user.left';
    }
}