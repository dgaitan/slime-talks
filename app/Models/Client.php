<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

/**
 * Client Model
 * 
 * Represents a client application that can authenticate and access the API.
 * Each client has a unique public key and domain for authentication.
 * 
 * @property int $id Auto-incrementing primary key
 * @property string $uuid Unique identifier for public-facing operations
 * @property string $name Client application name
 * @property string $domain Allowed domain for this client
 * @property array|null $allowed_ips Array of allowed IP addresses
 * @property array|null $allowed_subdomains Array of allowed subdomains
 * @property string $public_key Public key for API authentication
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder|Client withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Client withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Client onlyTrashed()
 * 
 * @example
 * $client = Client::create([
 *     'name' => 'My App',
 *     'domain' => 'myapp.com',
 *     'public_key' => 'pk_test_1234567890'
 * ]);
 */
class Client extends Model implements Authenticatable
{
    use HasApiTokens, SoftDeletes, HasFactory, AuthenticatableTrait;

    protected $fillable = [
        'uuid',
        'name',
        'domain',
        'allowed_ips',
        'allowed_subdomains',
        'public_key',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'allowed_subdomains' => 'array',
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
     * // Route: /api/v1/clients/{client}
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
     * Automatically generates a UUID when creating a new client if not provided.
     * 
     * @return void
     * 
     * @example
     * $client = new Client(['name' => 'Test']);
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
}
