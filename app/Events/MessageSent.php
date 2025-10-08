<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Message Sent Event
 *
 * Broadcasts when a new message is sent to a channel.
 * This event is broadcast to all participants in the channel.
 *
 * @package App\Events
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Message $message The message that was sent
     */
    public function __construct(
        public readonly Message $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("channel.{$this->message->channel->uuid}"),
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
            'message' => [
                'id' => $this->message->uuid,
                'type' => $this->message->type,
                'content' => $this->message->content,
                'metadata' => $this->message->metadata,
                'sender' => [
                    'id' => $this->message->sender->uuid,
                    'name' => $this->message->sender->name,
                ],
                'channel' => [
                    'id' => $this->message->channel->uuid,
                    'name' => $this->message->channel->name,
                    'type' => $this->message->channel->type,
                ],
                'created_at' => $this->message->created_at->toISOString(),
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
        return 'message.sent';
    }
}