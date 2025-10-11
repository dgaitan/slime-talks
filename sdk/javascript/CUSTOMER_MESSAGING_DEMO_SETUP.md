# Customer-Centric Messaging Demo Setup Guide

This guide will help you set up and run the customer-centric messaging demo that showcases the new Slime Talks API endpoints.

## 🎯 What This Demo Shows

The customer-centric messaging demo demonstrates:

- **Active Customers Sidebar**: Shows customers ordered by latest message activity
- **Customer-Centric Conversations**: Groups channels by customer pairs
- **Cross-Channel Messaging**: Retrieves all messages between two customers regardless of channel
- **Direct Messaging**: Send messages directly to customers using general channels
- **Real-time Updates**: Live message updates and typing indicators
- **Modern UI**: WhatsApp/Slack-inspired interface

## 📁 Files Overview

### Core SDK Files
- `slime-talks-sdk.js` - Main SDK with all REST API methods
- `slime-talks-realtime.js` - Real-time messaging client
- `customer-messaging-example.js` - Customer-centric messaging application class

### Demo Files
- `customer-messaging-demo.html` - Complete demo with mock data
- `CUSTOMER_MESSAGING_DEMO_SETUP.md` - This setup guide

## 🚀 Quick Start (Demo Mode)

1. **Open the Demo**
   ```bash
   # Navigate to the SDK directory
   cd sdk/javascript/
   
   # Open the demo in your browser
   open customer-messaging-demo.html
   ```

2. **Try the Demo**
   - Click on customers in the sidebar to view conversations
   - Send messages using the input field
   - See how customers are ordered by activity
   - Experience the modern messaging interface

## 🔧 Production Setup

### 1. Configure API Credentials

Update the demo HTML file with your actual API credentials:

```javascript
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://your-api-domain.com/api/v1',
    secretKey: 'sk_test_1234567890abcdef',
    publicKey: 'pk_test_1234567890abcdef',
    origin: 'https://yourdomain.com',
    pusherKey: 'your-pusher-key',
    pusherCluster: 'us2',
});
```

### 2. Set Up Real-time Messaging

1. **Configure Pusher**
   - Get your Pusher credentials from [pusher.com](https://pusher.com)
   - Update the Pusher key and cluster in your SDK configuration

2. **Update Laravel Broadcasting**
   - Ensure your Laravel app has broadcasting configured
   - Run `php artisan config:cache` after updating broadcasting settings

### 3. Test with Real Data

Replace the mock SDK with the real one:

```javascript
// Remove this line:
// const sdk = new MockSlimeTalksSDK();

// Use the real SDK instead:
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://your-api-domain.com/api/v1',
    secretKey: 'your-secret-key',
    publicKey: 'your-public-key',
    origin: 'https://yourdomain.com',
    pusherKey: 'your-pusher-key',
    pusherCluster: 'us2',
});
```

## 🎨 Customization

### Styling

The demo uses modern CSS with:
- Flexbox layouts
- Smooth animations
- Responsive design
- Custom scrollbars
- Typing indicators

Key CSS classes to customize:
- `.customer-item` - Sidebar customer items
- `.message` - Individual messages
- `.conversation-header` - Chat header
- `.message-input-wrapper` - Input area

### Functionality

Extend the `CustomerMessagingApp` class to add:
- Message search
- File uploads
- Emoji reactions
- Message status indicators
- Unread message counts

## 📊 API Endpoints Used

The demo showcases these new customer-centric endpoints:

### 1. Active Customers
```javascript
// Get customers ordered by latest message activity
const customers = await sdk.getActiveCustomers({ limit: 50 });
```

**Response Format:**
```json
{
  "object": "list",
  "data": [
    {
      "object": "customer",
      "id": "cus_1234567890",
      "name": "John Doe",
      "email": "john@example.com",
      "latest_message_at": 1640995200,
      "created": 1640908800,
      "livemode": false
    }
  ],
  "has_more": false,
  "total_count": 6
}
```

### 2. Channels by Email (Grouped)
```javascript
// Get channels grouped by recipient
const channels = await sdk.getChannelsByEmail('customer@example.com');
```

**Response Format:**
```json
{
  "data": {
    "conversations": [
      {
        "recipient": {
          "object": "customer",
          "id": "cus_0987654321",
          "name": "Jane Smith",
          "email": "jane@example.com"
        },
        "channels": [
          {
            "object": "channel",
            "id": "ch_1234567890",
            "type": "general",
            "name": "General",
            "updated_at": 1640995200
          }
        ],
        "latest_message_at": 1640995200
      }
    ]
  },
  "total_count": 1
}
```

### 3. Messages Between Customers
```javascript
// Get all messages between two customers
const messages = await sdk.getMessagesBetweenCustomers(
  'customer1@example.com',
  'customer2@example.com'
);
```

**Response Format:**
```json
{
  "object": "list",
  "data": [
    {
      "object": "message",
      "id": "msg_1234567890",
      "channel_id": "ch_1234567890",
      "sender_id": "cus_1234567890",
      "type": "text",
      "content": "Hello!",
      "metadata": null,
      "created": 1640995200,
      "livemode": false
    }
  ],
  "has_more": false,
  "total_count": 1
}
```

### 4. Send to Customer
```javascript
// Send message directly to customer (uses general channel)
const message = await sdk.sendToCustomer({
  sender_email: 'sender@example.com',
  recipient_email: 'recipient@example.com',
  type: 'text',
  content: 'Hello!'
});
```

## 🔄 Real-time Events

The demo handles these real-time events:

### Message Events
```javascript
realtime.on('message.sent', (data) => {
  // New message received
  console.log('New message:', data.message);
  displayMessage(data.message);
});
```

### Typing Events
```javascript
realtime.on('typing.started', (data) => {
  // User started typing
  showTypingIndicator(data.customer);
});

realtime.on('typing.stopped', (data) => {
  // User stopped typing
  hideTypingIndicator(data.customer);
});
```

### Presence Events
```javascript
realtime.on('user.joined', (data) => {
  // User joined channel
  showUserJoined(data.user);
});

realtime.on('user.left', (data) => {
  // User left channel
  showUserLeft(data.user);
});
```

## 🧪 Testing

### Test the Demo

1. **Open Browser Console**
   - Check for any JavaScript errors
   - Monitor API calls in Network tab
   - Verify real-time connections

2. **Test Different Scenarios**
   - Send messages between customers
   - Check customer ordering in sidebar
   - Verify real-time updates work
   - Test typing indicators

### Test with Real API

1. **Create Test Customers**
   ```javascript
   // Create customers for testing
   const customer1 = await sdk.createCustomer({
     name: 'Test User 1',
     email: 'test1@example.com'
   });
   
   const customer2 = await sdk.createCustomer({
     name: 'Test User 2',
     email: 'test2@example.com'
   });
   ```

2. **Send Test Messages**
   ```javascript
   // Send messages between customers
   await sdk.sendToCustomer({
     sender_email: 'test1@example.com',
     recipient_email: 'test2@example.com',
     type: 'text',
     content: 'Hello from test user 1!'
   });
   ```

## 🐛 Troubleshooting

### Common Issues

1. **CORS Errors**
   - Ensure your domain is whitelisted in Laravel
   - Check Origin header matches your domain

2. **Authentication Errors**
   - Verify API keys are correct
   - Check token expiration
   - Ensure customer exists and belongs to client

3. **Real-time Connection Issues**
   - Verify Pusher credentials
   - Check Laravel broadcasting configuration
   - Ensure WebSocket connection is allowed

4. **Empty Customer Lists**
   - Create test customers first
   - Send some messages to generate activity
   - Check customer belongs to correct client

### Debug Mode

Enable debug logging:

```javascript
const sdk = new SlimeTalksSDK({
  // ... other config
  debug: true  // Enable debug logging
});
```

## 📱 Mobile Responsiveness

The demo is designed to work on mobile devices:

- **Touch-friendly**: Large tap targets
- **Responsive**: Adapts to different screen sizes
- **Keyboard-friendly**: Proper input handling
- **Swipe gestures**: Can be added for navigation

## 🚀 Deployment

### Static Hosting

Deploy to any static hosting service:
- GitHub Pages
- Netlify
- Vercel
- AWS S3 + CloudFront

### Security Considerations

1. **API Keys**: Never expose secret keys in frontend code
2. **CORS**: Configure proper origins in Laravel
3. **HTTPS**: Always use HTTPS in production
4. **Rate Limiting**: Implement client-side rate limiting

## 📈 Performance Tips

1. **Pagination**: Use limit/offset for large datasets
2. **Caching**: Cache customer lists and messages
3. **Debouncing**: Debounce typing indicators
4. **Lazy Loading**: Load messages on demand
5. **Connection Pooling**: Reuse real-time connections

## 🔗 Integration Examples

### React Integration

```jsx
import { useState, useEffect } from 'react';
import { SlimeTalksSDK } from './slime-talks-sdk';

function CustomerMessaging() {
  const [sdk] = useState(() => new SlimeTalksSDK({
    apiUrl: process.env.REACT_APP_SLIME_TALKS_API_URL,
    secretKey: process.env.REACT_APP_SLIME_TALKS_SECRET_KEY,
    publicKey: process.env.REACT_APP_SLIME_TALKS_PUBLIC_KEY,
    origin: window.location.origin,
    pusherKey: process.env.REACT_APP_PUSHER_KEY,
  }));

  const [customers, setCustomers] = useState([]);
  const [messages, setMessages] = useState([]);

  useEffect(() => {
    loadActiveCustomers();
  }, []);

  const loadActiveCustomers = async () => {
    try {
      const response = await sdk.getActiveCustomers({ limit: 50 });
      setCustomers(response.data);
    } catch (error) {
      console.error('Failed to load customers:', error);
    }
  };

  return (
    <div className="messaging-app">
      {/* Your React components here */}
    </div>
  );
}
```

### Vue.js Integration

```vue
<template>
  <div class="messaging-app">
    <div class="sidebar">
      <div 
        v-for="customer in customers" 
        :key="customer.id"
        @click="selectCustomer(customer)"
      >
        {{ customer.name }}
      </div>
    </div>
    <div class="messages">
      <div 
        v-for="message in messages" 
        :key="message.id"
      >
        {{ message.content }}
      </div>
    </div>
  </div>
</template>

<script>
import { SlimeTalksSDK } from './slime-talks-sdk';

export default {
  data() {
    return {
      sdk: new SlimeTalksSDK({
        apiUrl: process.env.VUE_APP_SLIME_TALKS_API_URL,
        secretKey: process.env.VUE_APP_SLIME_TALKS_SECRET_KEY,
        publicKey: process.env.VUE_APP_SLIME_TALKS_PUBLIC_KEY,
        origin: window.location.origin,
        pusherKey: process.env.VUE_APP_PUSHER_KEY,
      }),
      customers: [],
      messages: [],
      selectedCustomer: null
    };
  },
  async mounted() {
    await this.loadActiveCustomers();
  },
  methods: {
    async loadActiveCustomers() {
      try {
        const response = await this.sdk.getActiveCustomers({ limit: 50 });
        this.customers = response.data;
      } catch (error) {
        console.error('Failed to load customers:', error);
      }
    },
    async selectCustomer(customer) {
      this.selectedCustomer = customer;
      await this.loadConversation(customer.email);
    },
    async loadConversation(email) {
      try {
        const response = await this.sdk.getMessagesBetweenCustomers(
          'current@example.com',
          email,
          { limit: 50 }
        );
        this.messages = response.data;
      } catch (error) {
        console.error('Failed to load conversation:', error);
      }
    }
  }
};
</script>
```

## 📚 Next Steps

1. **Explore the Code**: Review the example implementation
2. **Customize the UI**: Adapt the styling to your brand
3. **Add Features**: Implement search, file uploads, etc.
4. **Integrate**: Add to your existing application
5. **Deploy**: Push to production

## 🆘 Support

If you need help:

1. **Check Documentation**: Review the main API documentation
2. **Review Examples**: Study the provided code examples
3. **Test Endpoints**: Use the API directly to verify functionality
4. **Contact Support**: Reach out for technical assistance

## 🎉 Conclusion

The customer-centric messaging demo showcases the powerful new endpoints that make it easy to build modern messaging interfaces. The combination of activity-ordered customers, grouped conversations, and cross-channel messaging provides everything needed for a professional messaging application.

Happy coding! 🚀
