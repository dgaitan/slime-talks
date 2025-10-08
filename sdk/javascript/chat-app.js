/**
 * Slime Talks Chat Application
 * 
 * A complete chat application using the Slime Talks SDK.
 * This file contains the JavaScript logic for a production-ready chat interface.
 * 
 * @package SlimeTalks\SDK\JavaScript
 * @author Laravel Slime Talks
 * @version 1.0.0
 */

class SlimeTalksChatApp {
    /**
     * Create a new chat application instance
     * 
     * @param {Object} config Configuration object
     * @param {string} config.apiUrl - API base URL
     * @param {string} config.secretKey - API secret key
     * @param {string} config.publicKey - API public key
     * @param {string} config.origin - Origin domain
     * @param {string} config.pusherKey - Pusher key for real-time
     * @param {string} config.pusherCluster - Pusher cluster
     * @param {Object} config.currentUser - Current user information
     */
    constructor(config) {
        this.config = config;
        this.sdk = null;
        this.realtime = null;
        this.currentChannel = null;
        this.typingTimeout = null;
        this.messageCache = new Map();
        
        // DOM elements
        this.elements = {
            chatMessages: document.getElementById('chat-messages'),
            messageInput: document.getElementById('message-input'),
            sendButton: document.getElementById('send-button'),
            emojiButton: document.getElementById('emoji-button'),
            connectionStatus: document.getElementById('connection-status'),
            typingIndicators: document.getElementById('typing-indicators'),
        };

        this.init();
    }

    /**
     * Initialize the chat application
     */
    async init() {
        try {
            // Initialize SDK
            this.sdk = new SlimeTalksSDK(this.config);
            
            // Initialize real-time if Pusher is available
            if (window.Pusher) {
                this.realtime = this.sdk.initRealtime({
                    id: this.config.currentUser.id,
                    name: this.config.currentUser.name
                });

                this.setupRealtimeHandlers();
            }

            // Setup event listeners
            this.setupEventListeners();
            
            // Enable input
            this.elements.messageInput.disabled = false;
            this.elements.sendButton.disabled = false;
            
            this.updateConnectionStatus('connected');
            
            console.log('Chat application initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize chat application:', error);
            this.updateConnectionStatus('disconnected');
            this.showError('Failed to initialize chat application');
        }
    }

    /**
     * Setup real-time event handlers
     */
    setupRealtimeHandlers() {
        if (!this.realtime) return;

        this.realtime.onConnected = () => {
            console.log('Connected to real-time messaging');
            this.updateConnectionStatus('connected');
        };

        this.realtime.onDisconnected = () => {
            console.log('Disconnected from real-time messaging');
            this.updateConnectionStatus('disconnected');
        };

        this.realtime.onError = (error) => {
            console.error('Real-time error:', error);
            this.updateConnectionStatus('disconnected');
            this.showError('Connection error: ' + error.message);
        };

        this.realtime.onMaxReconnectAttempts = () => {
            console.error('Max reconnection attempts reached');
            this.showError('Unable to connect to real-time messaging');
        };
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Send message on Enter key
        this.elements.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Send button click
        this.elements.sendButton.addEventListener('click', () => this.sendMessage());

        // Typing indicator
        this.elements.messageInput.addEventListener('input', () => this.handleTyping());

        // Emoji button
        this.elements.emojiButton.addEventListener('click', () => this.showEmojiPicker());

        // Message input focus
        this.elements.messageInput.addEventListener('focus', () => {
            this.elements.messageInput.placeholder = 'Type a message...';
        });

        this.elements.messageInput.addEventListener('blur', () => {
            this.elements.messageInput.placeholder = 'I\'ll start looking into it!';
        });
    }

    /**
     * Join a channel
     * 
     * @param {string} channelUuid Channel UUID
     * @param {Object} options Channel options
     */
    async joinChannel(channelUuid, options = {}) {
        try {
            this.currentChannel = channelUuid;

            // Load message history
            await this.loadMessageHistory(channelUuid);

            // Join real-time channel
            if (this.realtime) {
                this.realtime.joinChannel(channelUuid, {
                    onMessage: (data) => {
                        console.log('New message received:', data.message);
                        this.displayMessage(data.message);
                    },
                    onTypingStarted: (data) => {
                        console.log('User started typing:', data.typing.user);
                        this.showTypingIndicator(data.typing.user);
                    },
                    onTypingStopped: (data) => {
                        console.log('User stopped typing:', data.typing.user);
                        this.hideTypingIndicator(data.typing.user);
                    },
                    onUserJoined: (data) => {
                        console.log('User joined:', data.user);
                        this.showNotification(`${data.user.name} joined the channel`);
                    },
                    onUserLeft: (data) => {
                        console.log('User left:', data.user);
                        this.showNotification(`${data.user.name} left the channel`);
                    }
                });
            }

            console.log(`Joined channel: ${channelUuid}`);
            
        } catch (error) {
            console.error('Failed to join channel:', error);
            this.showError('Failed to join channel');
        }
    }

    /**
     * Load message history for a channel
     * 
     * @param {string} channelUuid Channel UUID
     */
    async loadMessageHistory(channelUuid) {
        try {
            const messages = await this.sdk.getChannelMessages(channelUuid, {
                limit: 50
            });

            // Clear existing messages
            this.elements.chatMessages.innerHTML = '';

            // Display messages
            messages.data.forEach(message => {
                this.displayMessage(message);
            });

            this.scrollToBottom();
            
        } catch (error) {
            console.error('Failed to load message history:', error);
            this.showError('Failed to load message history');
        }
    }

    /**
     * Display a message in the chat
     * 
     * @param {Object} message Message object
     */
    displayMessage(message) {
        // Avoid duplicate messages
        if (this.messageCache.has(message.id)) {
            return;
        }
        this.messageCache.set(message.id, message);

        const messageEl = document.createElement('div');
        messageEl.className = `message ${message.sender?.id === this.config.currentUser.id ? 'sent' : 'received'}`;
        messageEl.dataset.messageId = message.id;

        // Avatar
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.style.backgroundImage = `url(${this.getUserAvatar(message.sender)})`;

        // Message content
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';

        // Message bubble
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';

        const text = document.createElement('div');
        text.className = 'message-text';
        text.textContent = message.content;

        const timestamp = document.createElement('div');
        timestamp.className = 'message-timestamp';
        timestamp.textContent = this.formatTimestamp(message.created);

        bubble.appendChild(text);
        messageContent.appendChild(bubble);
        messageContent.appendChild(timestamp);

        // Add reactions if any
        if (message.metadata?.reactions && message.metadata.reactions.length > 0) {
            const reactionsEl = document.createElement('div');
            reactionsEl.className = 'reactions';
            
            message.metadata.reactions.forEach(reaction => {
                const reactionEl = document.createElement('div');
                reactionEl.className = 'reaction';
                reactionEl.textContent = reaction.emoji;
                reactionEl.title = `${reaction.count} reaction${reaction.count > 1 ? 's' : ''}`;
                reactionEl.addEventListener('click', () => this.addReaction(message.id, reaction.emoji));
                reactionsEl.appendChild(reactionEl);
            });
            
            messageContent.appendChild(reactionsEl);
        }

        messageEl.appendChild(avatar);
        messageEl.appendChild(messageContent);

        this.elements.chatMessages.appendChild(messageEl);
        this.scrollToBottom();
    }

    /**
     * Send a message
     */
    async sendMessage() {
        const content = this.elements.messageInput.value.trim();
        if (!content || !this.currentChannel) return;

        try {
            // Disable input while sending
            this.elements.messageInput.disabled = true;
            this.elements.sendButton.disabled = true;

            const message = await this.sdk.sendMessage({
                channel_uuid: this.currentChannel,
                sender_uuid: this.config.currentUser.id,
                type: 'text',
                content: content,
                metadata: {
                    timestamp: Date.now()
                }
            });

            // Clear input
            this.elements.messageInput.value = '';
            
            // Stop typing indicator
            if (this.realtime) {
                this.realtime.stopTyping(this.currentChannel);
            }

            // The message will be displayed via real-time event
            console.log('Message sent successfully:', message);
            
        } catch (error) {
            console.error('Failed to send message:', error);
            this.showError('Failed to send message');
        } finally {
            // Re-enable input
            this.elements.messageInput.disabled = false;
            this.elements.sendButton.disabled = false;
            this.elements.messageInput.focus();
        }
    }

    /**
     * Handle typing indicator
     */
    handleTyping() {
        if (!this.realtime || !this.currentChannel) return;

        // Send typing indicator
        this.realtime.sendTyping(this.currentChannel);

        // Clear existing timeout
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        // Auto-stop typing after 3 seconds
        this.typingTimeout = setTimeout(() => {
            this.realtime.stopTyping(this.currentChannel);
        }, 3000);
    }

    /**
     * Show typing indicator
     * 
     * @param {Object} user User object
     */
    showTypingIndicator(user) {
        const existing = document.getElementById(`typing-${user.id}`);
        if (existing) return;

        const indicator = document.createElement('div');
        indicator.id = `typing-${user.id}`;
        indicator.className = 'typing-indicator';

        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.style.backgroundImage = `url(${this.getUserAvatar(user)})`;

        const text = document.createElement('span');
        text.textContent = `${user.name} is typing`;

        const dots = document.createElement('div');
        dots.className = 'typing-dots';
        dots.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';

        indicator.appendChild(avatar);
        indicator.appendChild(text);
        indicator.appendChild(dots);

        this.elements.typingIndicators.appendChild(indicator);
        this.scrollToBottom();
    }

    /**
     * Hide typing indicator
     * 
     * @param {Object} user User object
     */
    hideTypingIndicator(user) {
        const indicator = document.getElementById(`typing-${user.id}`);
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Add reaction to a message
     * 
     * @param {string} messageId Message ID
     * @param {string} emoji Reaction emoji
     */
    async addReaction(messageId, emoji) {
        try {
            // In a real implementation, you'd send the reaction via API
            console.log(`Adding reaction ${emoji} to message ${messageId}`);
            
            // For demo purposes, just log it
            this.showNotification(`Added reaction: ${emoji}`);
            
        } catch (error) {
            console.error('Failed to add reaction:', error);
            this.showError('Failed to add reaction');
        }
    }

    /**
     * Show emoji picker
     */
    showEmojiPicker() {
        // In a real app, you'd implement an emoji picker
        console.log('Emoji picker would open here');
        this.showNotification('Emoji picker would open here');
    }

    /**
     * Get user avatar
     * 
     * @param {Object} user User object
     * @returns {string} Avatar URL
     */
    getUserAvatar(user) {
        if (!user) return this.getDefaultAvatar();
        
        // If user has avatar, return it
        if (user.avatar) return user.avatar;
        
        // Generate avatar based on user ID
        return this.generateAvatar(user.id, user.name);
    }

    /**
     * Generate avatar URL
     * 
     * @param {string} userId User ID
     * @param {string} userName User name
     * @returns {string} Avatar URL
     */
    generateAvatar(userId, userName) {
        const colors = ['#4066F3', '#FF69B4', '#32CD32', '#FFD700', '#FF6347', '#9370DB'];
        const colorIndex = userId.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % colors.length;
        const color = colors[colorIndex];
        
        const initials = userName ? userName.split(' ').map(n => n[0]).join('').toUpperCase() : '?';
        
        return `data:image/svg+xml;base64,${btoa(`
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20" cy="20" r="20" fill="${color}"/>
                <text x="20" y="26" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="14" font-weight="bold">${initials}</text>
            </svg>
        `)}`;
    }

    /**
     * Get default avatar
     * 
     * @returns {string} Default avatar URL
     */
    getDefaultAvatar() {
        return this.generateAvatar('default', 'User');
    }

    /**
     * Format timestamp
     * 
     * @param {string|number} timestamp Timestamp
     * @returns {string} Formatted timestamp
     */
    formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        
        if (minutes < 1) return 'just now';
        if (minutes < 60) return `${minutes}m ago`;
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        
        return date.toLocaleDateString();
    }

    /**
     * Update connection status
     * 
     * @param {string} status Connection status
     */
    updateConnectionStatus(status) {
        if (!this.elements.connectionStatus) return;
        
        this.elements.connectionStatus.textContent = status === 'connected' ? 'Connected' : 
                                                   status === 'connecting' ? 'Connecting...' : 'Disconnected';
        this.elements.connectionStatus.className = `connection-status ${status}`;
    }

    /**
     * Show notification
     * 
     * @param {string} message Notification message
     */
    showNotification(message) {
        // In a real app, you'd use a proper notification system
        console.log('Notification:', message);
        
        // Simple browser notification
        if (Notification.permission === 'granted') {
            new Notification('Slime Talks Chat', {
                body: message,
                icon: '/favicon.ico'
            });
        }
    }

    /**
     * Show error message
     * 
     * @param {string} message Error message
     */
    showError(message) {
        console.error('Error:', message);
        alert(message); // In a real app, use a proper error display
    }

    /**
     * Scroll to bottom of chat
     */
    scrollToBottom() {
        if (this.elements.chatMessages) {
            this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
        }
    }

    /**
     * Leave current channel
     */
    leaveChannel() {
        if (this.realtime && this.currentChannel) {
            this.realtime.leaveChannel(this.currentChannel);
            this.currentChannel = null;
        }
    }

    /**
     * Disconnect from real-time
     */
    disconnect() {
        if (this.realtime) {
            this.realtime.disconnect();
        }
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SlimeTalksChatApp;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.SlimeTalksChatApp = SlimeTalksChatApp;
}
