# Real-time Messaging Setup Guide

This guide explains how to set up and configure real-time messaging features for the Slime Talks Messaging API using Laravel Broadcasting and Pusher.

## Prerequisites

- Laravel 12 application with Slime Talks API
- Pusher account and credentials
- Node.js and npm (for frontend integration)

## 1. Environment Configuration

Add the following environment variables to your `.env` file:

```bash
# Broadcasting Configuration
BROADCAST_CONNECTION=pusher

# Pusher Configuration
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_APP_CLUSTER=your-pusher-cluster

# Broadcasting Auth Endpoint
BROADCAST_AUTH_ENDPOINT=/broadcasting/auth
```

## 2. Frontend Dependencies

Install the required JavaScript libraries:

```bash
# Install Pusher JavaScript SDK
npm install pusher-js

# Or include via CDN
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
```

## 3. Broadcasting Configuration

The broadcasting configuration is already set up in `config/broadcasting.php`. Make sure your Pusher credentials are correctly configured.

## 4. Channel Authorization

Channel authorization is configured in `routes/channels.php`. The following channels are available:

- `private-channel.{channelUuid}` - For message broadcasting
- `presence-presence.channel.{channelUuid}` - For user presence

## 5. Events

The following events are available for real-time messaging:

### MessageSent
Broadcasts when a new message is sent to a channel.

**Channel:** `private-channel.{channelUuid}`
**Event:** `message.sent`

**Data:**
```json
{
  "message": {
    "id": "msg_uuid",
    "type": "text",
    "content": "Message content",
    "metadata": {},
    "sender": {
      "id": "customer_uuid",
      "name": "Customer Name"
    },
    "channel": {
      "id": "channel_uuid",
      "name": "Channel Name",
      "type": "general"
    },
    "created_at": "2024-01-01T00:00:00.000Z"
  }
}
```

### TypingStarted
Broadcasts when a user starts typing in a channel.

**Channel:** `private-channel.{channelUuid}`
**Event:** `typing.started`

**Data:**
```json
{
  "typing": {
    "user": {
      "id": "customer_uuid",
      "name": "Customer Name"
    },
    "channel": {
      "id": "channel_uuid",
      "name": "Channel Name"
    },
    "started_at": "2024-01-01T00:00:00.000Z"
  }
}
```

### TypingStopped
Broadcasts when a user stops typing in a channel.

**Channel:** `private-channel.{channelUuid}`
**Event:** `typing.stopped`

**Data:**
```json
{
  "typing": {
    "user": {
      "id": "customer_uuid",
      "name": "Customer Name"
    },
    "channel": {
      "id": "channel_uuid",
      "name": "Channel Name"
    },
    "stopped_at": "2024-01-01T00:00:00.000Z"
  }
}
```

### UserJoinedChannel
Broadcasts when a user joins a channel.

**Channels:** `private-channel.{channelUuid}`, `presence-presence.channel.{channelUuid}`
**Event:** `user.joined`

**Data:**
```json
{
  "user": {
    "id": "customer_uuid",
    "name": "Customer Name",
    "email": "customer@example.com",
    "joined_at": "2024-01-01T00:00:00.000Z"
  },
  "channel": {
    "id": "channel_uuid",
    "name": "Channel Name",
    "type": "general"
  }
}
```

### UserLeftChannel
Broadcasts when a user leaves a channel.

**Channels:** `private-channel.{channelUuid}`, `presence-presence.channel.{channelUuid}`
**Event:** `user.left`

**Data:**
```json
{
  "user": {
    "id": "customer_uuid",
    "name": "Customer Name",
    "email": "customer@example.com",
    "left_at": "2024-01-01T00:00:00.000Z"
  },
  "channel": {
    "id": "channel_uuid",
    "name": "Channel Name",
    "type": "general"
  }
}
```

## 6. JavaScript Client Usage

### Basic Setup

```javascript
// Initialize the realtime client
const realtime = new SlimeTalksRealtime({
    apiUrl: 'https://api.slime-talks.com/api/v1',
    pusherKey: 'your-pusher-key',
    pusherCluster: 'us2',
    token: 'your-api-token',
    publicKey: 'your-public-key',
    origin: 'https://yourdomain.com',
    user: {
        id: 'customer-uuid',
        name: 'Customer Name'
    }
});
```

### Join a Channel

```javascript
const channel = realtime.joinChannel('channel-uuid', {
    onMessage: (data) => {
        console.log('New message:', data.message);
        displayMessage(data.message);
    },
    
    onTypingStarted: (data) => {
        console.log('User started typing:', data.typing.user);
        showTypingIndicator(data.typing.user);
    },
    
    onTypingStopped: (data) => {
        console.log('User stopped typing:', data.typing.user);
        hideTypingIndicator(data.typing.user);
    }
});
```

### Send Messages

```javascript
// Send a message
await realtime.sendMessage('channel-uuid', {
    sender_uuid: 'customer-uuid',
    type: 'text',
    content: 'Hello, world!',
    metadata: {
        priority: 'normal'
    }
});
```

### Typing Indicators

```javascript
// Send typing indicator
realtime.sendTyping('channel-uuid');

// Stop typing indicator
realtime.stopTyping('channel-uuid');
```

### Presence Channels

```javascript
// Join presence channel for online users
const presenceChannel = realtime.joinPresenceChannel('channel-uuid', {
    onSubscriptionSucceeded: (members) => {
        console.log('Online users:', members);
        updateOnlineUsersList(members);
    },
    
    onMemberAdded: (member) => {
        console.log('User came online:', member);
        addOnlineUser(member);
    },
    
    onMemberRemoved: (member) => {
        console.log('User went offline:', member);
        removeOnlineUser(member);
    }
});
```

## 7. Testing

Run the real-time tests to ensure everything is working correctly:

```bash
php artisan test --filter="RealtimeTest"
```

## 8. Production Considerations

### Security
- Ensure proper channel authorization
- Validate user permissions before broadcasting
- Use HTTPS for all connections
- Implement rate limiting for typing indicators

### Performance
- Use connection pooling for high-traffic applications
- Implement message queuing for reliability
- Monitor WebSocket connection limits
- Consider Redis for session storage

### Monitoring
- Monitor Pusher connection metrics
- Track message delivery rates
- Monitor WebSocket connection stability
- Set up alerts for connection failures

## 9. Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Verify Pusher credentials
   - Check channel authorization logic
   - Ensure proper headers are sent

2. **Connection Issues**
   - Check network connectivity
   - Verify Pusher cluster configuration
   - Monitor connection limits

3. **Message Delivery**
   - Verify channel names match
   - Check event names are correct
   - Ensure proper data structure

### Debug Mode

Enable debug logging in your JavaScript client:

```javascript
const realtime = new SlimeTalksRealtime({
    // ... other config
    debug: true
});
```

## 10. Support

For issues with real-time messaging:

1. Check the [Laravel Broadcasting Documentation](https://laravel.com/docs/12.x/broadcasting)
2. Review [Pusher Documentation](https://pusher.com/docs)
3. Check the test suite for examples
4. Contact support with specific error messages

---

**Happy real-time messaging! 🚀**
