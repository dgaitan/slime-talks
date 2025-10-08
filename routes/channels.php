<?php

use App\Models\Channel;
use App\Models\Customer;
use Illuminate\Support\Facades\Broadcast;

// User model broadcasting (default Laravel)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel broadcasting - only channel participants can listen
Broadcast::channel('channel.{channelUuid}', function ($user, $channelUuid) {
    // Find the channel
    $channel = Channel::where('uuid', $channelUuid)->first();
    
    if (!$channel) {
        return false;
    }
    
    // Check if the authenticated user (client) owns this channel
    if ($user->id !== $channel->client_id) {
        return false;
    }
    
    return true;
});

// Presence channel for online users in a channel
Broadcast::channel('presence.channel.{channelUuid}', function ($user, $channelUuid) {
    // Find the channel
    $channel = Channel::where('uuid', $channelUuid)->first();
    
    if (!$channel) {
        return false;
    }
    
    // Check if the authenticated user (client) owns this channel
    if ($user->id !== $channel->client_id) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'domain' => $user->domain,
    ];
});
