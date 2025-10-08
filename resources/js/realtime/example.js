/**
 * Slime Talks Realtime Usage Example
 * 
 * This example demonstrates how to use the SlimeTalksRealtime client
 * for real-time messaging functionality.
 * 
 * @package App\Resources\Js\Realtime
 * @author Laravel Slime Talks
 * @version 1.0.0
 */

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

// Set up global event handlers
realtime.onConnected = () => {
    console.log('Connected to realtime messaging');
    document.getElementById('connection-status').textContent = 'Connected';
    document.getElementById('connection-status').className = 'status connected';
};

realtime.onDisconnected = () => {
    console.log('Disconnected from realtime messaging');
    document.getElementById('connection-status').textContent = 'Disconnected';
    document.getElementById('connection-status').className = 'status disconnected';
};

realtime.onError = (error) => {
    console.error('Realtime error:', error);
    showNotification('Connection error: ' + error.message, 'error');
};

realtime.onMaxReconnectAttempts = () => {
    console.error('Max reconnection attempts reached');
    showNotification('Unable to connect to realtime messaging', 'error');
};

// Join a channel
function joinChannel(channelUuid) {
    const channel = realtime.joinChannel(channelUuid, {
        onMessage: (data) => {
            displayMessage(data.message);
        },
        
        onTypingStarted: (data) => {
            showTypingIndicator(data.typing.user);
        },
        
        onTypingStopped: (data) => {
            hideTypingIndicator(data.typing.user);
        },
        
        onUserJoined: (data) => {
            showNotification(`${data.user.name} joined the channel`, 'info');
            updateOnlineUsers();
        },
        
        onUserLeft: (data) => {
            showNotification(`${data.user.name} left the channel`, 'info');
            updateOnlineUsers();
        }
    });
    
    return channel;
}

// Join presence channel for online users
function joinPresenceChannel(channelUuid) {
    const presenceChannel = realtime.joinPresenceChannel(channelUuid, {
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
    
    return presenceChannel;
}

// Send a message
async function sendMessage(channelUuid, content, type = 'text') {
    try {
        const messageData = {
            sender_uuid: 'customer-uuid',
            type: type,
            content: content,
            metadata: {
                timestamp: Date.now()
            }
        };
        
        const response = await realtime.sendMessage(channelUuid, messageData);
        console.log('Message sent:', response);
        
        // Clear input field
        document.getElementById('message-input').value = '';
        
    } catch (error) {
        console.error('Failed to send message:', error);
        showNotification('Failed to send message', 'error');
    }
}

// Handle typing indicators
let typingTimeout;

function handleTyping(channelUuid) {
    // Send typing indicator
    realtime.sendTyping(channelUuid);
    
    // Clear existing timeout
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
    
    // Auto-stop typing after 3 seconds
    typingTimeout = setTimeout(() => {
        realtime.stopTyping(channelUuid);
    }, 3000);
}

// UI Helper Functions
function displayMessage(message) {
    const messagesContainer = document.getElementById('messages');
    const messageElement = document.createElement('div');
    messageElement.className = 'message';
    messageElement.innerHTML = `
        <div class="message-header">
            <strong>${message.sender.name}</strong>
            <span class="timestamp">${new Date(message.created_at).toLocaleTimeString()}</span>
        </div>
        <div class="message-content">${message.content}</div>
    `;
    
    messagesContainer.appendChild(messageElement);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function showTypingIndicator(user) {
    const typingContainer = document.getElementById('typing-indicators');
    const indicator = document.createElement('div');
    indicator.id = `typing-${user.id}`;
    indicator.className = 'typing-indicator';
    indicator.textContent = `${user.name} is typing...`;
    
    typingContainer.appendChild(indicator);
}

function hideTypingIndicator(user) {
    const indicator = document.getElementById(`typing-${user.id}`);
    if (indicator) {
        indicator.remove();
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function updateOnlineUsers() {
    // Update online users count or list
    const onlineCount = document.querySelectorAll('.online-user').length;
    document.getElementById('online-count').textContent = `${onlineCount} online`;
}

function updateOnlineUsersList(members) {
    const onlineUsersContainer = document.getElementById('online-users');
    onlineUsersContainer.innerHTML = '';
    
    Object.values(members.members).forEach(member => {
        const userElement = document.createElement('div');
        userElement.className = 'online-user';
        userElement.textContent = member.name;
        onlineUsersContainer.appendChild(userElement);
    });
}

function addOnlineUser(member) {
    const onlineUsersContainer = document.getElementById('online-users');
    const userElement = document.createElement('div');
    userElement.className = 'online-user';
    userElement.textContent = member.name;
    onlineUsersContainer.appendChild(userElement);
}

function removeOnlineUser(member) {
    const userElement = document.querySelector(`[data-user-id="${member.id}"]`);
    if (userElement) {
        userElement.remove();
    }
}

// Example usage
document.addEventListener('DOMContentLoaded', function() {
    const channelUuid = 'your-channel-uuid';
    
    // Join the channel
    const channel = joinChannel(channelUuid);
    
    // Join presence channel
    const presenceChannel = joinPresenceChannel(channelUuid);
    
    // Set up message sending
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const content = messageInput.value.trim();
        if (content) {
            sendMessage(channelUuid, content);
        }
    });
    
    // Set up typing indicator
    messageInput.addEventListener('input', function() {
        handleTyping(channelUuid);
    });
    
    // Handle connection status
    const connectionStatus = realtime.getConnectionState();
    console.log('Connection state:', connectionStatus);
});
