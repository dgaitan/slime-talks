# Slime Talks Chat Demo Setup

This guide will help you set up and run the Slime Talks chat demo that matches the design in the screenshot.

## 📁 Files Overview

- **`chat-demo.html`** - Basic demo with static messages (matches screenshot design)
- **`production-chat.html`** - Production-ready demo with real API integration
- **`chat-app.js`** - Complete chat application class
- **`slime-talks-sdk.js`** - Main SDK for API calls
- **`slime-talks-realtime.js`** - Real-time messaging client

## 🚀 Quick Start

### Option 1: Basic Demo (Static Messages)

1. **Open the basic demo:**
   ```bash
   # Simply open the HTML file in your browser
   open sdk/javascript/chat-demo.html
   ```

2. **Features included:**
   - ✅ Exact design from screenshot
   - ✅ Message bubbles with avatars
   - ✅ Timestamps and reactions
   - ✅ Typing indicators
   - ✅ Responsive design
   - ✅ Static demo messages

### Option 2: Production Demo (Real API)

1. **Configure your credentials:**
   ```javascript
   // Edit production-chat.html and update the CONFIG object:
   const CONFIG = {
       apiUrl: 'https://your-api-domain.com/api/v1',
       secretKey: 'sk_live_your_secret_key',
       publicKey: 'pk_live_your_public_key',
       origin: 'https://yourdomain.com',
       pusherKey: 'your-pusher-key',
       pusherCluster: 'us2',
   };
   ```

2. **Start a local server:**
   ```bash
   # Using Python
   python -m http.server 8000
   
   # Using Node.js
   npx serve .
   
   # Using PHP
   php -S localhost:8000
   ```

3. **Open in browser:**
   ```
   http://localhost:8000/sdk/javascript/production-chat.html
   ```

## 🎨 Design Features

The chat demo includes all the visual elements from the screenshot:

### Message Layout
- **Left-aligned messages** (received) with light gray bubbles
- **Right-aligned messages** (sent) with black bubbles
- **Rounded corners** with different corner styles
- **Avatars** with generated initials and colors

### Interactive Elements
- **Typing indicators** with animated dots
- **Message reactions** with emoji support
- **Real-time updates** via WebSocket
- **Connection status** indicator

### Responsive Design
- **Mobile-friendly** layout
- **Touch-optimized** buttons
- **Smooth animations** and transitions
- **Custom scrollbars**

## 🔧 Customization

### Styling
Edit the CSS in the HTML files to match your brand:

```css
/* Change primary colors */
.message.sent .message-bubble {
    background-color: #your-brand-color;
}

/* Change avatar colors */
.avatar {
    border-color: #your-border-color;
}
```

### Functionality
Modify the JavaScript to add features:

```javascript
// Add custom message types
if (message.type === 'image') {
    // Handle image messages
}

// Add custom reactions
const customReactions = ['👍', '❤️', '😂', '😮', '😢'];
```

## 📱 Mobile Features

The demo is fully responsive and includes:

- **Touch-friendly** interface
- **Swipe gestures** (can be added)
- **Keyboard handling** for mobile
- **Viewport optimization**

## 🔌 API Integration

### Real-time Messaging
```javascript
// Join a channel
await chatApp.joinChannel('ch_your_channel_id');

// Send a message
await chatApp.sendMessage('Hello, world!');

// Handle real-time events
chatApp.realtime.onMessage = (data) => {
    console.log('New message:', data.message);
};
```

### Message History
```javascript
// Load message history
const messages = await chatApp.sdk.getChannelMessages('ch_channel_id', {
    limit: 50
});
```

## 🎯 Use Cases

### Customer Support
```javascript
// Create support channel
const channel = await sdk.createChannel({
    type: 'custom',
    name: 'Support Ticket #12345',
    customer_uuids: ['cus_customer_id', 'cus_agent_id']
});
```

### Team Communication
```javascript
// Create team channel
const teamChannel = await sdk.createChannel({
    type: 'custom',
    name: 'Engineering Team',
    customer_uuids: ['cus_dev1', 'cus_dev2', 'cus_lead']
});
```

### Direct Messages
```javascript
// Create direct message
const dmChannel = await sdk.createChannel({
    type: 'general',
    customer_uuids: ['cus_user1', 'cus_user2']
});
```

## 🛠️ Development

### Adding New Features

1. **Custom message types:**
   ```javascript
   // In chat-app.js
   displayMessage(message) {
       if (message.type === 'file') {
           // Handle file messages
       }
   }
   ```

2. **Custom reactions:**
   ```javascript
   // Add reaction picker
   showReactionPicker(messageId) {
       // Implement reaction picker UI
   }
   ```

3. **Message search:**
   ```javascript
   // Add search functionality
   searchMessages(query) {
       // Implement message search
   }
   ```

### Testing

```javascript
// Test real-time connection
chatApp.realtime.onConnected = () => {
    console.log('✅ Connected to real-time');
};

// Test message sending
chatApp.sendMessage('Test message');
```

## 📊 Performance

### Optimization Tips

1. **Message pagination:**
   ```javascript
   // Load messages in batches
   const messages = await sdk.getChannelMessages(channelId, {
       limit: 20,
       starting_after: lastMessageId
   });
   ```

2. **Connection management:**
   ```javascript
   // Reconnect on network issues
   chatApp.realtime.onDisconnected = () => {
       setTimeout(() => chatApp.realtime.connect(), 5000);
   };
   ```

3. **Memory management:**
   ```javascript
   // Limit message cache
   if (this.messageCache.size > 1000) {
       const oldest = this.messageCache.keys().next().value;
       this.messageCache.delete(oldest);
   }
   ```

## 🐛 Troubleshooting

### Common Issues

1. **CORS errors:**
   - Ensure your API allows your origin
   - Check the `origin` configuration

2. **WebSocket connection fails:**
   - Verify Pusher credentials
   - Check network connectivity
   - Ensure SSL certificates are valid

3. **Messages not appearing:**
   - Check API credentials
   - Verify channel permissions
   - Check browser console for errors

### Debug Mode

```javascript
// Enable debug logging
const CONFIG = {
    // ... other config
    debug: true
};

// Check connection status
console.log('Connection status:', chatApp.realtime.getConnectionState());
```

## 📚 Next Steps

1. **Integrate with your backend:**
   - Set up API endpoints
   - Configure authentication
   - Implement user management

2. **Add advanced features:**
   - File uploads
   - Message editing
   - Message threading
   - Voice messages

3. **Deploy to production:**
   - Set up SSL certificates
   - Configure CDN
   - Monitor performance

## 🆘 Support

- **Documentation**: Check the main README files
- **Issues**: Create an issue on GitHub
- **Support**: Email support@slime-talks.com

---

**Ready to build amazing chat experiences! 🚀**
