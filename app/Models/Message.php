<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Message Model
 *
 * Represents a message sent in a channel by a customer.
 * Messages support different types (text, image, file) and can contain metadata.
 * All messages are scoped to a specific client for data isolation.
 *
 * @package App\Models
 * @author Laravel Slime Talks
 * @version 1.0.0
 *
 * @property int $id
 * @property string $uuid
 * @property int $client_id
 * @property int $channel_id
 * @property int $sender_id
 * @property string $type
 * @property string $content
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Client $client
 * @property-read Channel $channel
 * @property-read Customer $sender
 */
class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'client_id',
        'channel_id',
        'sender_id',
        'type',
        'content',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     *
     * Generates UUID for new messages automatically.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Message $message) {
            if (empty($message->uuid)) {
                $message->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the client that owns the message.
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the channel that the message belongs to.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the customer that sent the message.
     *
     * @return BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }
}
