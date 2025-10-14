# Slime Talks JavaScript SDK

A comprehensive JavaScript SDK for the Slime Talks Messaging API with built-in real-time messaging support via Pusher.

## Features

- ✅ Complete REST API client
- ✅ Real-time messaging with Pusher
- ✅ Typing indicators
- ✅ Presence channels (online users)
- ✅ TypeScript-friendly
- ✅ Promise-based API
- ✅ Automatic reconnection
- ✅ Error handling

## Installation

### Option 1: Include via CDN

```html
<!-- Include Pusher (required for real-time features) -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<!-- Include Slime Talks Realtime Client -->
<script src="path/to/slime-talks-realtime.js"></script>

<!-- Include Slime Talks SDK -->
<script src="path/to/slime-talks-sdk.js"></script>
```

### Option 2: Install via npm (if using a bundler)

```bash
npm install pusher-js
```

Copy the SDK files to your project:

```bash
cp slime-talks-sdk.js /path/to/your/project/src/
cp slime-talks-realtime.js /path/to/your/project/src/
```

Import in your JavaScript:

```javascript
import Pusher from 'pusher-js';
import { SlimeTalksSDK } from './slime-talks-sdk.js';
```

## Quick Start

### Basic Setup

```javascript
// Initialize the SDK
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://api.slime-talks.com/api/v1',
    secretKey: 'your-api-secret-key',
    publicKey: 'your-api-public-key',
    origin: 'https://yourdomain.com',
    pusherKey: 'your-pusher-key',        // Optional: for real-time features
    pusherCluster: 'us2',                 // Optional: Pusher cluster
    timeout: 30000                        // Optional: request timeout in ms
});
```

### With Real-time Features

```javascript
// Initialize the SDK
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://api.slime-talks.com/api/v1',
    secretKey: 'your-api-secret-key',
    publicKey: 'your-api-public-key',
    origin: 'https://yourdomain.com',
    pusherKey: 'your-pusher-key',
    pusherCluster: 'us2',
});

// Initialize real-time messaging
const realtime = sdk.initRealtime({
    id: 'customer-uuid',
    name: 'Customer Name'
});

// Set up connection handlers
realtime.onConnected = () => {
    console.log('Connected to real-time messaging');
};

realtime.onDisconnected = () => {
    console.log('Disconnected from real-time messaging');
};

realtime.onError = (error) => {
    console.error('Real-time error:', error);
};
```

## API Methods

### Customer Management

```javascript
// Create a customer
try {
    const customer = await sdk.createCustomer({
        name: 'John Doe',
        email: 'john@example.com',
        metadata: {
            department: 'Engineering',
            role: 'Developer'
        }
    });
    console.log('Customer created:', customer);
} catch (error) {
    console.error('Error:', error.message);
}

// Get a customer
const customer = await sdk.getCustomer('cus_1234567890');

// List customers with pagination
const customers = await sdk.listCustomers({
    limit: 20,
    starting_after: 'cus_1234567890'
});

// Get active customers (ordered by latest message activity)
const activeCustomers = await sdk.getActiveCustomers({
    limit: 20,
    starting_after: 'cus_1234567890'
});

console.log('Customers:', customers.data);
console.log('Active customers:', activeCustomers.data);
console.log('Has more:', customers.has_more);
console.log('Total count:', customers.total_count);
```

### Channel Management

```javascript
// Create a general channel (direct message)
const channel = await sdk.createChannel({
    type: 'general',
    customer_uuids: ['cus_1234567890', 'cus_0987654321']
});

// Create a custom channel (group chat)
const groupChannel = await sdk.createChannel({
    type: 'custom',
    name: 'Engineering Team',
    customer_uuids: ['cus_1234567890', 'cus_0987654321', 'cus_1122334455']
});

// Get a channel
const channelData = await sdk.getChannel('ch_1234567890');

// List channels
const channels = await sdk.listChannels({ limit: 10 });

// Get channels for a customer
const userChannels = await sdk.getCustomerChannels('cus_1234567890');

// Get channels for a customer by email (grouped by recipient)
const groupedChannels = await sdk.getChannelsByEmail('customer@example.com');
console.log('Conversations:', groupedChannels.data.conversations);
```

### Message Management

```javascript
// Send a text message
const message = await sdk.sendMessage({
    channel_uuid: 'ch_1234567890',
    sender_uuid: 'cus_1234567890',
    type: 'text',
    content: 'Hello, this is a test message!',
    metadata: {
        priority: 'high',
        tags: ['important', 'urgent']
    }
});

// Get messages from a channel
const messages = await sdk.getChannelMessages('ch_1234567890', {
    limit: 50,
    starting_after: 'msg_1234567890'
});

// Get messages for a customer
const customerMessages = await sdk.getCustomerMessages('cus_1234567890', {
    limit: 50
});

// Get messages between two customers (all channels)
const conversationMessages = await sdk.getMessagesBetweenCustomers(
    'customer1@example.com',
    'customer2@example.com',
    { limit: 50 }
);

// Send a message directly to a customer (uses general channel)
const directMessage = await sdk.sendToCustomer({
    sender_email: 'sender@example.com',
    recipient_email: 'recipient@example.com',
    type: 'text',
    content: 'Hello! How are you?',
    metadata: {
        priority: 'normal'
    }
});

// Iterate through messages
messages.data.forEach(msg => {
    console.log(`${msg.id}: ${msg.content}`);
});
```

## Customer-Centric Messaging

The SDK includes special methods for building customer-centric messaging interfaces (like WhatsApp or Slack):

### Customer-Centric Messaging Example

```javascript
class CustomerMessagingApp {
    constructor(sdk) {
        this.sdk = sdk;
        this.currentUser = null;
        this.selectedCustomer = null;
    }

    async init(user) {
        this.currentUser = user;
        
        // Load active customers for sidebar
        await this.loadActiveCustomers();
        
        // Initialize real-time messaging
        this.realtime = this.sdk.initRealtime(user);
        this.setupRealtimeHandlers();
    }

    async loadActiveCustomers() {
        try {
            // Get customers ordered by latest message activity
            const response = await this.sdk.getActiveCustomers({ limit: 50 });
            const customers = response.data;
            
            // Update sidebar with customers who have sent messages recently
            this.updateSidebar(customers);
            
        } catch (error) {
            console.error('Failed to load active customers:', error);
        }
    }

    async selectCustomer(customer) {
        this.selectedCustomer = customer;
        
        // Load conversation between current user and selected customer
        await this.loadConversation(customer.email);
        
        // Join real-time channel for this conversation
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

            // Add message to UI
            this.addMessageToUI(message);
            
            // Clear input
            this.clearInput();

        } catch (error) {
            console.error('Failed to send message:', error);
        }
    }

    async getChannelsByCustomer(customerEmail) {
        try {
            // Get channels grouped by recipient for sidebar
            const response = await this.sdk.getChannelsByEmail(customerEmail);
            
            // Each conversation shows the other customer and latest activity
            return response.data.conversations;
            
        } catch (error) {
            console.error('Failed to get channels:', error);
            return [];
        }
    }
}

// Usage
const app = new CustomerMessagingApp(sdk);
await app.init({
    id: 'customer-uuid',
    name: 'Current User',
    email: 'current@example.com'
});
```

### Customer-Centric UI Patterns

```javascript
// Load customers for sidebar (ordered by activity)
const activeCustomers = await sdk.getActiveCustomers({ limit: 20 });

// Each customer shows:
// - Name and avatar
// - Latest message timestamp
// - Unread count (if implemented)

activeCustomers.data.forEach(customer => {
    console.log(`${customer.name}: Last active ${customer.latest_message_at}`);
});

// When customer is selected, load conversation
const messages = await sdk.getMessagesBetweenCustomers(
    'current@example.com',
    'selected@example.com'
);

// Messages include all channels between the two customers
messages.data.forEach(message => {
    console.log(`From ${message.sender_id}: ${message.content}`);
});

// Send new message (automatically uses general channel)
await sdk.sendToCustomer({
    sender_email: 'current@example.com',
    recipient_email: 'selected@example.com',
    type: 'text',
    content: 'Hello!'
});
```

## Real-time Messaging

### Join a Channel

```javascript
const realtime = sdk.getRealtime();

// Join a channel and set up event handlers
const channelConnection = realtime.joinChannel('ch_1234567890', {
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
    },
    
    onUserJoined: (data) => {
        console.log('User joined:', data.user);
        showNotification(`${data.user.name} joined the channel`);
    },
    
    onUserLeft: (data) => {
        console.log('User left:', data.user);
        showNotification(`${data.user.name} left the channel`);
    }
});

// Leave the channel when done
channelConnection.leave();
```

### Typing Indicators

```javascript
const realtime = sdk.getRealtime();

// Send typing indicator
realtime.sendTyping('ch_1234567890');

// Stop typing indicator
realtime.stopTyping('ch_1234567890');

// Auto-typing with input field
const messageInput = document.getElementById('message-input');
let typingTimeout;

messageInput.addEventListener('input', () => {
    realtime.sendTyping('ch_1234567890');
    
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        realtime.stopTyping('ch_1234567890');
    }, 3000);
});
```

### Presence Channels (Online Users)

```javascript
const realtime = sdk.getRealtime();

// Join presence channel to see online users
const presenceChannel = realtime.joinPresenceChannel('ch_1234567890', {
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

## Complete Chat Example

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Slime Talks Chat</title>
    <style>
        #chat-container { max-width: 600px; margin: 20px auto; }
        #messages { border: 1px solid #ccc; height: 400px; overflow-y: auto; padding: 10px; }
        .message { margin: 10px 0; padding: 8px; background: #f0f0f0; border-radius: 5px; }
        .typing-indicator { color: #666; font-style: italic; }
        #message-form { display: flex; gap: 10px; margin-top: 10px; }
        #message-input { flex: 1; padding: 8px; }
        button { padding: 8px 16px; }
    </style>
</head>
<body>
    <div id="chat-container">
        <h2>Chat Room</h2>
        <div id="messages"></div>
        <div id="typing-indicators"></div>
        <form id="message-form">
            <input type="text" id="message-input" placeholder="Type a message..." />
            <button type="submit">Send</button>
        </form>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="slime-talks-realtime.js"></script>
    <script src="slime-talks-sdk.js"></script>
    <script>
        // Initialize SDK
        const sdk = new SlimeTalksSDK({
            apiUrl: 'https://api.slime-talks.com/api/v1',
            secretKey: 'your-api-secret-key',
            publicKey: 'your-api-public-key',
            origin: 'https://yourdomain.com',
            pusherKey: 'your-pusher-key',
            pusherCluster: 'us2',
        });

        // Initialize real-time
        const realtime = sdk.initRealtime({
            id: 'current-customer-uuid',
            name: 'Current User'
        });

        // Configuration
        const CHANNEL_UUID = 'ch_1234567890';
        const SENDER_UUID = 'current-customer-uuid';

        // DOM elements
        const messagesDiv = document.getElementById('messages');
        const typingDiv = document.getElementById('typing-indicators');
        const messageForm = document.getElementById('message-form');
        const messageInput = document.getElementById('message-input');

        // Join channel
        const channel = realtime.joinChannel(CHANNEL_UUID, {
            onMessage: (data) => {
                displayMessage(data.message);
            },
            onTypingStarted: (data) => {
                showTypingIndicator(data.typing.user);
            },
            onTypingStopped: (data) => {
                hideTypingIndicator(data.typing.user);
            }
        });

        // Load message history
        async function loadMessages() {
            try {
                const messages = await sdk.getChannelMessages(CHANNEL_UUID, { limit: 50 });
                messages.data.forEach(msg => displayMessage(msg));
            } catch (error) {
                console.error('Failed to load messages:', error);
            }
        }

        // Display message
        function displayMessage(message) {
            const messageEl = document.createElement('div');
            messageEl.className = 'message';
            messageEl.innerHTML = `
                <strong>${message.sender?.name || 'Unknown'}</strong>
                <p>${message.content}</p>
                <small>${new Date(message.created_at).toLocaleTimeString()}</small>
            `;
            messagesDiv.appendChild(messageEl);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // Typing indicators
        let typingTimeout;

        function showTypingIndicator(user) {
            const indicator = document.createElement('div');
            indicator.id = `typing-${user.id}`;
            indicator.className = 'typing-indicator';
            indicator.textContent = `${user.name} is typing...`;
            typingDiv.appendChild(indicator);
        }

        function hideTypingIndicator(user) {
            const indicator = document.getElementById(`typing-${user.id}`);
            if (indicator) indicator.remove();
        }

        messageInput.addEventListener('input', () => {
            realtime.sendTyping(CHANNEL_UUID);
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                realtime.stopTyping(CHANNEL_UUID);
            }, 3000);
        });

        // Send message
        messageForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const content = messageInput.value.trim();
            if (!content) return;

            try {
                await sdk.sendMessage({
                    channel_uuid: CHANNEL_UUID,
                    sender_uuid: SENDER_UUID,
                    type: 'text',
                    content: content
                });

                messageInput.value = '';
                realtime.stopTyping(CHANNEL_UUID);
            } catch (error) {
                console.error('Failed to send message:', error);
                alert('Failed to send message');
            }
        });

        // Load initial messages
        loadMessages();
    </script>
</body>
</html>
```

## Error Handling

```javascript
try {
    const customer = await sdk.createCustomer({
        name: 'John Doe',
        email: 'john@example.com'
    });
} catch (error) {
    if (error instanceof SlimeTalksError) {
        console.error('API Error:', error.message);
        console.error('Status Code:', error.status);
        console.error('Error Data:', error.data);
        
        // Handle specific errors
        if (error.status === 422) {
            console.error('Validation failed');
        } else if (error.status === 401) {
            console.error('Authentication failed');
        }
    } else {
        console.error('Unexpected error:', error);
    }
}
```

## Advanced Usage

### Custom Request Timeout

```javascript
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://api.slime-talks.com/api/v1',
    secretKey: 'your-secret-key',
    publicKey: 'your-public-key',
    origin: 'https://yourdomain.com',
    timeout: 60000  // 60 seconds
});
```

### Pagination Helper

```javascript
async function getAllCustomers() {
    const allCustomers = [];
    let hasMore = true;
    let startingAfter = null;

    while (hasMore) {
        const response = await sdk.listCustomers({
            limit: 100,
            starting_after: startingAfter
        });

        allCustomers.push(...response.data);
        hasMore = response.has_more;
        
        if (hasMore && response.data.length > 0) {
            startingAfter = response.data[response.data.length - 1].id;
        }
    }

    return allCustomers;
}
```

### React Integration

```jsx
import { useState, useEffect } from 'react';
import { SlimeTalksSDK } from './slime-talks-sdk';

function ChatComponent() {
    const [sdk] = useState(() => new SlimeTalksSDK({
        apiUrl: process.env.REACT_APP_SLIME_TALKS_API_URL,
        secretKey: process.env.REACT_APP_SLIME_TALKS_SECRET_KEY,
        publicKey: process.env.REACT_APP_SLIME_TALKS_PUBLIC_KEY,
        origin: window.location.origin,
        pusherKey: process.env.REACT_APP_PUSHER_KEY,
    }));

    const [messages, setMessages] = useState([]);
    const [realtime, setRealtime] = useState(null);

    useEffect(() => {
        // Initialize real-time
        const rt = sdk.initRealtime({
            id: 'customer-uuid',
            name: 'Customer Name'
        });

        // Join channel
        rt.joinChannel('ch_1234567890', {
            onMessage: (data) => {
                setMessages(prev => [...prev, data.message]);
            }
        });

        setRealtime(rt);

        return () => {
            rt.disconnect();
        };
    }, [sdk]);

    const sendMessage = async (content) => {
        try {
            await sdk.sendMessage({
                channel_uuid: 'ch_1234567890',
                sender_uuid: 'customer-uuid',
                type: 'text',
                content: content
            });
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    };

    return (
        <div>
            <div className="messages">
                {messages.map(msg => (
                    <div key={msg.id}>{msg.content}</div>
                ))}
            </div>
            <input onKeyPress={(e) => {
                if (e.key === 'Enter') {
                    sendMessage(e.target.value);
                    e.target.value = '';
                }
            }} />
        </div>
    );
}
```

## TypeScript Support

The SDK is TypeScript-friendly. You can create type definitions:

```typescript
interface Customer {
    object: 'customer';
    id: string;
    name: string;
    email: string;
    metadata?: Record<string, any>;
    created: number;
    livemode: boolean;
}

interface Channel {
    object: 'channel';
    id: string;
    type: 'general' | 'custom';
    name: string;
    customers: Customer[];
    created: number;
    livemode: boolean;
}

interface Message {
    object: 'message';
    id: string;
    type: 'text' | 'image' | 'file';
    content: string;
    metadata?: Record<string, any>;
    created: number;
    livemode: boolean;
}
```

## Best Practices

1. **Store Credentials Securely**
   - Never commit API keys to version control
   - Use environment variables
   - Rotate keys regularly

2. **Handle Errors Gracefully**
   - Always use try-catch blocks
   - Provide user-friendly error messages
   - Log errors for debugging

3. **Implement Reconnection Logic**
   - The real-time client handles reconnection automatically
   - Implement retry logic for API calls if needed

4. **Optimize Performance**
   - Use pagination for large lists
   - Cache frequently accessed data
   - Debounce typing indicators

5. **Test Thoroughly**
   - Test with real-time features enabled/disabled
   - Test error scenarios
   - Test with poor network conditions

## Support

For issues or questions:
- Check the [API Documentation](../../API_DOCUMENTATION.md)
- Review the [Real-time Setup Guide](../../REALTIME_SETUP.md)
- Contact support: support@slime-talks.com

## License

MIT License
