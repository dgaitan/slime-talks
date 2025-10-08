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
 * Customer Model
 * 
 * Represents a customer within a client application.
 * Each customer belongs to a specific client and can participate in channels and send messages.
 * 
 * @property int $id Auto-incrementing primary key
 * @property string $uuid Unique identifier for public-facing operations
 * @property int $client_id Foreign key to the client
 * @property string $name Customer's display name
 * @property string $email Customer's email address (unique per client)
 * @property array|null $metadata Additional customer data (avatar, preferences, etc.)
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 * 
 * @property-read \App\Models\Client $client The client that owns this customer
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Channel[] $channels Channels this customer belongs to
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Message[] $messages Messages sent by this customer
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer onlyTrashed()
 * 
 * @example
 * $customer = Customer::create([
 *     'client_id' => 1,
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'metadata' => ['avatar' => 'https://example.com/avatar.jpg']
 * ]);
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'client_id',
        'name',
        'email',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
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
     * // Route: /api/v1/customers/{customer}
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
     * Automatically generates a UUID when creating a new customer if not provided.
     * 
     * @return void
     * 
     * @example
     * $customer = new Customer(['name' => 'John', 'email' => 'john@example.com']);
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
     * Get the client that owns the customer.
     * 
     * Establishes a belongs-to relationship with the Client model.
     * Each customer belongs to exactly one client.
     * 
     * @return BelongsTo The relationship instance
     * 
     * @example
     * $customer = Customer::find(1);
     * $client = $customer->client; // Returns the associated Client model
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the channels that the customer belongs to.
     * 
     * Establishes a many-to-many relationship with the Channel model.
     * Customers can belong to multiple channels through the 'channel_customer' pivot table.
     * 
     * @return BelongsToMany The relationship instance
     * 
     * @example
     * $customer = Customer::find(1);
     * $channels = $customer->channels; // Returns collection of associated channels
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_customer');
    }

    /**
     * Get the messages sent by the customer.
     * 
     * Establishes a one-to-many relationship with the Message model.
     * Returns all messages where this customer is the sender.
     * 
     * @return HasMany The relationship instance
     * 
     * @example
     * $customer = Customer::find(1);
     * $messages = $customer->messages; // Returns collection of messages sent by this customer
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
