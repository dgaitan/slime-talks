<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends Model
{
    use HasApiTokens, SoftDeletes, HasUuids, HasFactory;

    protected $fillable = [
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

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
