<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Client;

/**
 * Channel Service Interface
 * 
 * Defines the contract for channel business logic operations.
 * Provides methods for creating and managing channels with proper validation
 * and client isolation.
 * 
 * @package App\Services
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
interface ChannelServiceInterface
{
    /**
     * Create a new channel for a client.
     * 
     * Validates channel data, ensures customer validation,
     * and creates a new channel record with proper relationships.
     * 
     * @param Client $client The client creating the channel
     * @param array $data Channel data containing type and customer UUIDs
     * @return Channel Created channel instance
     * @throws \Illuminate\Validation\ValidationException When validation fails
     */
    public function create(Client $client, array $data): Channel;
}
