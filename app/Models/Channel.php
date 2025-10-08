<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Channel Model
 * 
 * Represents a communication channel between customers.
 * Channels can be either "general" (direct messaging) or "custom" (topic-specific).
 * Each channel belongs to a specific client and can have multiple customers.
 * 
 * @property int $id Auto-incrementing primary key
 * @property string $uuid Unique identifier for public-facing operations
 * @property int $client_id Foreign key to the client
 * @property string $type Channel type (general or custom)
 * @property string $name Channel name
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 * 
 * @property-read \App\Models\Client $client The client that owns this channel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Customer[] $customers Customers in this channel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Message[] $messages Messages in this channel
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Channel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel query()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel onlyTrashed()
 * 
 * @example
 * $channel = Channel::create([
 *     'client_id' => 1,
 *     'type' => 'general',
 *     'name' => 'general'
 * ]);
 */
class Channel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'client_id',
        'type',
        'name',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    protected $keyType = 'int';
    public $incrementing = true;

    /**
     * Get the route key for the model.
     * 
     * Returns the field name used for route model binding.
     * Uses 'uuid' instead of the default 'id' for public-facing URLs.
     * 
     * @return string The route key name
     * 
     * @example
     * // Route: /api/v1/channels/{channel}
     * // Will bind using the 'uuid' field instead of 'id'
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boot the model.
     * 
     * Sets up model event listeners and automatic UUID generation.
     * Automatically generates a UUID when creating a new channel if not provided.
     * 
     * @return void
     * 
     * @example
     * $channel = new Channel(['type' => 'general', 'name' => 'general']);
     * // UUID will be automatically generated on save
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the client that owns the channel.
     * 
     * Establishes a belongs-to relationship with the Client model.
     * Each channel belongs to exactly one client.
     * 
     * @return BelongsTo The relationship instance
     * 
     * @example
     * $channel = Channel::find(1);
     * $client = $channel->client; // Returns the associated Client model
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the customers that belong to the channel.
     * 
     * Establishes a many-to-many relationship with the Customer model.
     * Customers can belong to multiple channels through the 'channel_customer' pivot table.
     * 
     * @return BelongsToMany The relationship instance
     * 
     * @example
     * $channel = Channel::find(1);
     * $customers = $channel->customers; // Returns collection of associated customers
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'channel_customer');
    }

    /**
     * Get the messages in the channel.
     * 
     * Establishes a one-to-many relationship with the Message model.
     * Returns all messages that belong to this channel.
     * 
     * @return HasMany The relationship instance
     * 
     * @example
     * $channel = Channel::find(1);
     * $messages = $channel->messages; // Returns collection of messages in this channel
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
