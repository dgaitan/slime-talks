# Slime Talks SDK Guide

This guide provides instructions for using the Slime Talks SDKs (PHP and JavaScript) to integrate with the Slime Talks Messaging API.

## 📦 Available SDKs

### PHP SDK (Laravel)
- **Location**: `sdk/php/`
- **Best for**: Laravel applications, backend services
- **Features**: Complete REST API client using Laravel HTTP client

### JavaScript SDK
- **Location**: `sdk/javascript/`
- **Best for**: Web applications, React, Vue, vanilla JavaScript
- **Features**: REST API client + Real-time messaging with Pusher

## 🚀 Quick Start

### PHP SDK Setup

#### 1. Copy the SDK to Your Laravel Project

```bash
# Copy the SDK file
cp sdk/php/SlimeTalksClient.php /path/to/your/laravel/app/Services/SlimeTalks/

# Or create a dedicated directory
mkdir -p app/Services/SlimeTalks
cp sdk/php/SlimeTalksClient.php app/Services/SlimeTalks/
```

#### 2. Configure Your Environment

Add to `.env`:

```env
SLIME_TALKS_API_URL=https://api.slime-talks.com/api/v1
SLIME_TALKS_SECRET_KEY=sk_live_your_secret_key
SLIME_TALKS_PUBLIC_KEY=pk_live_your_public_key
SLIME_TALKS_ORIGIN=https://yourdomain.com
```

#### 3. Create Configuration File

Create `config/slimetalks.php`:

```php
<?php

return [
    'base_url' => env('SLIME_TALKS_API_URL'),
    'secret_key' => env('SLIME_TALKS_SECRET_KEY'),
    'public_key' => env('SLIME_TALKS_PUBLIC_KEY'),
    'origin' => env('SLIME_TALKS_ORIGIN'),
    'timeout' => 30,
    'retry' => [
        'times' => 3,
        'sleep' => 100,
    ],
];
```

#### 4. Register Service Provider

Create `app/Providers/SlimeTalksServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SlimeTalks\SDK\SlimeTalksClient;

class SlimeTalksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SlimeTalksClient::class, function ($app) {
            return new SlimeTalksClient(config('slimetalks'));
        });
    }
}
```

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\SlimeTalksServiceProvider::class,
],
```

#### 5. Use in Your Controllers

```php
<?php

namespace App\Http\Controllers;

use SlimeTalks\SDK\SlimeTalksClient;
use SlimeTalks\SDK\SlimeTalksException;

class ChatController extends Controller
{
    public function __construct(
        private SlimeTalksClient $slimeTalks
    ) {}

    public function sendMessage(Request $request)
    {
        try {
            $message = $this->slimeTalks->sendMessage([
                'channel_uuid' => $request->channel_uuid,
                'sender_uuid' => $request->sender_uuid,
                'type' => 'text',
                'content' => $request->content,
            ]);

            return response()->json($message);
        } catch (SlimeTalksException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### JavaScript SDK Setup

#### 1. Include Required Files

```html
<!-- Include Pusher (required for real-time) -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<!-- Include Slime Talks Real-time Client -->
<script src="sdk/javascript/slime-talks-realtime.js"></script>

<!-- Include Slime Talks SDK -->
<script src="sdk/javascript/slime-talks-sdk.js"></script>
```

#### 2. Initialize the SDK

```javascript
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://api.slime-talks.com/api/v1',
    secretKey: 'your-api-secret-key',
    publicKey: 'your-api-public-key',
    origin: 'https://yourdomain.com',
    pusherKey: 'your-pusher-key',
    pusherCluster: 'us2',
});
```

#### 3. Initialize Real-time (Optional)

```javascript
const realtime = sdk.initRealtime({
    id: 'customer-uuid',
    name: 'Customer Name'
});

// Join a channel
realtime.joinChannel('ch_1234567890', {
    onMessage: (data) => {
        console.log('New message:', data.message);
    },
    onTypingStarted: (data) => {
        console.log('User typing:', data.typing.user);
    }
});
```

## 🎯 Customer-Centric Messaging

The Slime Talks API now includes powerful customer-centric messaging features that make it easy to build WhatsApp/Slack-style interfaces:

### Key Features

- **Active Customers**: Get customers ordered by latest message activity
- **Grouped Conversations**: Channels grouped by customer pairs
- **Cross-Channel Messages**: Retrieve all messages between two customers
- **Direct Messaging**: Send messages directly to customers via general channels

### Customer-Centric Use Case: Build a Support Dashboard

#### PHP

```php
public function getSupportDashboard(Request $request)
{
    try {
        // Get active customers (ordered by latest message activity)
        $activeCustomers = $this->slimeTalks->getActiveCustomers([
            'limit' => 50
        ]);

        // Get conversations for a specific customer
        $conversations = $this->slimeTalks->getChannelsByEmail($request->customer_email);

        return response()->json([
            'active_customers' => $activeCustomers,
            'conversations' => $conversations
        ]);
    } catch (SlimeTalksException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function getCustomerConversation(Request $request)
{
    try {
        // Get all messages between two customers (across all channels)
        $messages = $this->slimeTalks->getMessagesBetweenCustomers(
            $request->customer1_email,
            $request->customer2_email,
            ['limit' => 50]
        );

        return response()->json($messages);
    } catch (SlimeTalksException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function sendDirectMessage(Request $request)
{
    try {
        // Send message directly to customer (uses general channel)
        $message = $this->slimeTalks->sendToCustomer([
            'sender_email' => $request->sender_email,
            'recipient_email' => $request->recipient_email,
            'type' => 'text',
            'content' => $request->content,
            'metadata' => $request->metadata ?? null
        ]);

        return response()->json($message);
    } catch (SlimeTalksException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

#### JavaScript

```javascript
class SupportDashboard {
    constructor(sdk) {
        this.sdk = sdk;
        this.currentUser = null;
        this.selectedCustomer = null;
    }

    async init(user) {
        this.currentUser = user;
        await this.loadActiveCustomers();
        
        // Initialize real-time messaging
        this.realtime = this.sdk.initRealtime(user);
        this.setupRealtimeHandlers();
    }

    async loadActiveCustomers() {
        try {
            // Get customers ordered by latest message activity
            const response = await this.sdk.getActiveCustomers({ limit: 50 });
            
            // Update sidebar with active customers
            this.updateSidebar(response.data);
            
        } catch (error) {
            console.error('Failed to load active customers:', error);
        }
    }

    async selectCustomer(customer) {
        this.selectedCustomer = customer;
        
        // Load conversation between current user and selected customer
        await this.loadConversation(customer.email);
        
        // Join real-time channel for live updates
        this.joinConversationChannel(customer.email);
    }

    async loadConversation(customerEmail) {
        try {
            // Get all messages between current user and selected customer
            const response = await this.sdk.getMessagesBetweenCustomers(
                this.currentUser.email,
                customerEmail,
                { limit: 50 }
            );
            
            // Display conversation messages
            this.displayMessages(response.data);
            
        } catch (error) {
            console.error('Failed to load conversation:', error);
        }
    }

    async sendMessage(content) {
        if (!this.selectedCustomer || !content.trim()) {
            return;
        }

        try {
            // Send message directly to customer (uses general channel)
            const message = await this.sdk.sendToCustomer({
                sender_email: this.currentUser.email,
                recipient_email: this.selectedCustomer.email,
                type: 'text',
                content: content.trim(),
            });

            // Add message to UI immediately
            this.addMessageToUI(message);
            
            // Clear input
            this.clearInput();

        } catch (error) {
            console.error('Failed to send message:', error);
        }
    }

    setupRealtimeHandlers() {
        if (!this.realtime) return;

        // Handle new messages
        this.realtime.on('message.sent', (data) => {
            // Only show message if it's from the current conversation
            if (this.selectedCustomer && 
                (data.message.sender_id === this.selectedCustomer.id || 
                 data.message.sender_id === this.currentUser.id)) {
                this.addMessageToUI(data.message);
            }
            
            // Update sidebar to show new activity
            this.loadActiveCustomers();
        });

        // Handle typing indicators
        this.realtime.on('typing.started', (data) => {
            if (this.selectedCustomer && data.customer.id === this.selectedCustomer.id) {
                this.showTypingIndicator(data.customer);
            }
        });

        this.realtime.on('typing.stopped', (data) => {
            if (this.selectedCustomer && data.customer.id === this.selectedCustomer.id) {
                this.hideTypingIndicator();
            }
        });
    }
}

// Usage
const dashboard = new SupportDashboard(sdk);
await dashboard.init({
    id: 'support-agent-uuid',
    name: 'Support Agent',
    email: 'agent@company.com'
});
```

### Demo Application

A complete demo application is available at:
- **HTML Demo**: `sdk/javascript/customer-messaging-demo.html`
- **Example Class**: `sdk/javascript/customer-messaging-example.js`
- **Setup Guide**: `sdk/javascript/CUSTOMER_MESSAGING_DEMO_SETUP.md`

The demo showcases:
- Modern WhatsApp/Slack-style interface
- Active customers sidebar
- Real-time messaging
- Typing indicators
- Cross-channel conversation history

## 📖 Common Use Cases

### Use Case 1: Create a Customer and Start a Conversation

#### PHP

```php
public function startConversation(Request $request)
{
    try {
        // Create customer
        $customer = $this->slimeTalks->createCustomer([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Create channel with support agent
        $channel = $this->slimeTalks->createChannel([
            'type' => 'general',
            'customer_uuids' => [$customer['id'], $request->agent_uuid]
        ]);

        // Send welcome message
        $message = $this->slimeTalks->sendMessage([
            'channel_uuid' => $channel['id'],
            'sender_uuid' => $request->agent_uuid,
            'type' => 'text',
            'content' => 'Welcome! How can I help you today?'
        ]);

        return response()->json([
            'customer' => $customer,
            'channel' => $channel,
            'message' => $message
        ]);
    } catch (SlimeTalksException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

#### JavaScript

```javascript
async function startConversation(name, email, agentUuid) {
    try {
        // Create customer
        const customer = await sdk.createCustomer({
            name: name,
            email: email
        });

        // Create channel
        const channel = await sdk.createChannel({
            type: 'general',
            customer_uuids: [customer.id, agentUuid]
        });

        // Send welcome message
        const message = await sdk.sendMessage({
            channel_uuid: channel.id,
            sender_uuid: agentUuid,
            type: 'text',
            content: 'Welcome! How can I help you today?'
        });

        return { customer, channel, message };
    } catch (error) {
        console.error('Failed to start conversation:', error);
        throw error;
    }
}
```

### Use Case 2: Real-time Chat Application

```javascript
// Initialize SDK with real-time
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://api.slime-talks.com/api/v1',
    secretKey: 'your-secret-key',
    publicKey: 'your-public-key',
    origin: window.location.origin,
    pusherKey: 'your-pusher-key',
});

const realtime = sdk.initRealtime({
    id: currentUser.uuid,
    name: currentUser.name
});

// Join channel
const channel = realtime.joinChannel(channelUuid, {
    onMessage: (data) => {
        appendMessage(data.message);
    },
    onTypingStarted: (data) => {
        showTypingIndicator(data.typing.user);
    },
    onTypingStopped: (data) => {
        hideTypingIndicator(data.typing.user);
    }
});

// Send message
async function sendMessage(content) {
    await sdk.sendMessage({
        channel_uuid: channelUuid,
        sender_uuid: currentUser.uuid,
        type: 'text',
        content: content
    });
}

// Handle typing
messageInput.addEventListener('input', () => {
    realtime.sendTyping(channelUuid);
});
```

### Use Case 3: Customer Support Ticket System

#### PHP

```php
public function createSupportTicket(Request $request)
{
    try {
        // Get or create customer
        $customer = $this->findOrCreateCustomer($request->email);

        // Create support channel
        $channel = $this->slimeTalks->createChannel([
            'type' => 'custom',
            'name' => "Support Ticket #{$request->ticket_id}",
            'customer_uuids' => [$customer['id'], $request->support_agent_uuid]
        ]);

        // Send initial message
        $message = $this->slimeTalks->sendMessage([
            'channel_uuid' => $channel['id'],
            'sender_uuid' => $customer['id'],
            'type' => 'text',
            'content' => $request->message,
            'metadata' => [
                'ticket_id' => $request->ticket_id,
                'priority' => $request->priority,
                'category' => $request->category
            ]
        ]);

        return response()->json([
            'ticket_id' => $request->ticket_id,
            'channel' => $channel,
            'message' => $message
        ]);
    } catch (SlimeTalksException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

### Use Case 4: Load Message History with Pagination

#### PHP

```php
public function getMessageHistory(string $channelUuid)
{
    try {
        $allMessages = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $response = $this->slimeTalks->getChannelMessages($channelUuid, [
                'limit' => 100,
                'starting_after' => $startingAfter
            ]);

            $allMessages = array_merge($allMessages, $response['data']);
            $hasMore = $response['has_more'];
            
            if ($hasMore && count($response['data']) > 0) {
                $startingAfter = end($response['data'])['id'];
            }
        }

        return response()->json($allMessages);
    } catch (SlimeTalksException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

#### JavaScript

```javascript
async function loadAllMessages(channelUuid) {
    const allMessages = [];
    let hasMore = true;
    let startingAfter = null;

    while (hasMore) {
        const response = await sdk.getChannelMessages(channelUuid, {
            limit: 100,
            starting_after: startingAfter
        });

        allMessages.push(...response.data);
        hasMore = response.has_more;
        
        if (hasMore && response.data.length > 0) {
            startingAfter = response.data[response.data.length - 1].id;
        }
    }

    return allMessages;
}
```

## 🔧 Advanced Features

### Custom Error Handling

#### PHP

```php
use SlimeTalks\SDK\SlimeTalksException;

try {
    $customer = $this->slimeTalks->createCustomer($data);
} catch (SlimeTalksException $e) {
    Log::error('Slime Talks API Error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'previous' => $e->getPrevious()?->getMessage()
    ]);

    if ($e->getCode() === 422) {
        return response()->json(['error' => 'Validation failed'], 422);
    } elseif ($e->getCode() === 401) {
        return response()->json(['error' => 'Authentication failed'], 401);
    }

    return response()->json(['error' => 'An error occurred'], 500);
}
```

#### JavaScript

```javascript
try {
    const customer = await sdk.createCustomer(data);
} catch (error) {
    if (error instanceof SlimeTalksError) {
        console.error('API Error:', error.message);
        
        if (error.status === 422) {
            alert('Validation failed: ' + error.message);
        } else if (error.status === 401) {
            alert('Authentication failed');
            // Redirect to login
        } else {
            alert('An error occurred');
        }
    } else {
        console.error('Unexpected error:', error);
    }
}
```

### Retry Logic (PHP)

The PHP SDK supports automatic retry logic:

```php
$client = new SlimeTalksClient([
    'base_url' => config('slimetalks.base_url'),
    'secret_key' => config('slimetalks.secret_key'),
    'public_key' => config('slimetalks.public_key'),
    'origin' => config('slimetalks.origin'),
    'timeout' => 30,
    'retry' => [
        'times' => 3,      // Retry up to 3 times
        'sleep' => 100,    // Wait 100ms between retries
    ],
]);
```

### Connection State Management (JavaScript)

```javascript
const realtime = sdk.getRealtime();

realtime.onConnected = () => {
    console.log('Connected to real-time messaging');
    updateConnectionStatus('connected');
};

realtime.onDisconnected = () => {
    console.log('Disconnected from real-time messaging');
    updateConnectionStatus('disconnected');
};

realtime.onError = (error) => {
    console.error('Real-time error:', error);
    showErrorNotification('Connection error: ' + error.message);
};

realtime.onMaxReconnectAttempts = () => {
    console.error('Max reconnection attempts reached');
    showErrorNotification('Unable to connect to real-time messaging');
};

// Get current connection state
const state = realtime.getConnectionState();
console.log('Connection state:', state); // 'connected', 'disconnected', or 'connecting'
```

## 📚 Additional Resources

- **[API Documentation](./API_DOCUMENTATION.md)** - Complete API reference
- **[Integration Guide](./INTEGRATION_GUIDE.md)** - Detailed integration instructions
- **[Real-time Setup](./REALTIME_SETUP.md)** - Real-time messaging configuration
- **[User Stories](./USER_STORIES.md)** - Feature documentation
- **[OpenAPI Specification](./swagger.yaml)** - API specification

## 🆘 Support

### Getting Help

1. **Documentation**: Check the comprehensive documentation above
2. **Examples**: Review the example code in the SDK README files
3. **Issues**: Create an issue on GitHub
4. **Support**: Email support@slime-talks.com

### Common Issues

**PHP SDK**
- Make sure you're using Laravel 10+ for HTTP client support
- Verify environment variables are set correctly
- Check that the service provider is registered

**JavaScript SDK**
- Include Pusher library before the SDK
- Verify CORS settings allow your origin
- Check browser console for errors
- Ensure API credentials are correct

## 📄 License

MIT License

---

**Ready to build amazing chat applications with Slime Talks! 🚀**
