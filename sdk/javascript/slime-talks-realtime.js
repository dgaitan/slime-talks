/**
 * Slime Talks Realtime Client
 * 
 * A JavaScript client for real-time messaging with the Slime Talks API.
 * Provides WebSocket connection management, message broadcasting, and typing indicators.
 * 
 * @package App\Resources\Js\Realtime
 * @author Laravel Slime Talks
 * @version 1.0.0
 */

class SlimeTalksRealtime {
    constructor(config) {
        this.config = {
            apiUrl: config.apiUrl || 'https://api.slime-talks.com/api/v1',
            pusherKey: config.pusherKey,
            pusherCluster: config.pusherCluster || 'us2',
            authEndpoint: config.authEndpoint || '/broadcasting/auth',
            ...config
        };
        
        this.pusher = null;
        this.channels = new Map();
        this.typingTimeouts = new Map();
        this.connectionState = 'disconnected';
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        
        this.init();
    }

    /**
     * Initialize the realtime client
     */
    init() {
        if (!window.Pusher) {
            console.error('Pusher library is required. Please include pusher-js.');
            return;
        }

        this.pusher = new Pusher(this.config.pusherKey, {
            cluster: this.config.pusherCluster,
            authEndpoint: this.config.authEndpoint,
            auth: {
                headers: {
                    'Authorization': `Bearer ${this.config.token}`,
                    'X-Public-Key': this.config.publicKey,
                    'Origin': this.config.origin,
                }
            },
            encrypted: true,
            enabledTransports: ['ws', 'wss']
        });

        this.setupEventListeners();
    }

    /**
     * Setup Pusher event listeners
     */
    setupEventListeners() {
        this.pusher.connection.bind('connected', () => {
            this.connectionState = 'connected';
            this.reconnectAttempts = 0;
            console.log('Connected to Slime Talks realtime');
            this.onConnected?.();
        });

        this.pusher.connection.bind('disconnected', () => {
            this.connectionState = 'disconnected';
            console.log('Disconnected from Slime Talks realtime');
            this.onDisconnected?.();
        });

        this.pusher.connection.bind('error', (error) => {
            console.error('Pusher connection error:', error);
            this.onError?.(error);
            this.handleReconnection();
        });
    }

    /**
     * Handle reconnection logic
     */
    handleReconnection() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
            
            console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
            
            setTimeout(() => {
                this.pusher.connect();
            }, delay);
        } else {
            console.error('Max reconnection attempts reached');
            this.onMaxReconnectAttempts?.();
        }
    }

    /**
     * Join a channel
     * 
     * @param {string} channelUuid - The channel UUID
     * @param {Object} callbacks - Event callbacks
     * @returns {Object} Channel object
     */
    joinChannel(channelUuid, callbacks = {}) {
        const channelName = `private-channel.${channelUuid}`;
        
        if (this.channels.has(channelUuid)) {
            console.warn(`Already connected to channel ${channelUuid}`);
            return this.channels.get(channelUuid);
        }

        const channel = this.pusher.subscribe(channelName);
        
        // Message events
        channel.bind('message.sent', (data) => {
            console.log('New message received:', data);
            callbacks.onMessage?.(data);
        });

        // Typing events
        channel.bind('typing.started', (data) => {
            console.log('User started typing:', data);
            callbacks.onTypingStarted?.(data);
        });

        channel.bind('typing.stopped', (data) => {
            console.log('User stopped typing:', data);
            callbacks.onTypingStopped?.(data);
        });

        // User presence events
        channel.bind('user.joined', (data) => {
            console.log('User joined channel:', data);
            callbacks.onUserJoined?.(data);
        });

        channel.bind('user.left', (data) => {
            console.log('User left channel:', data);
            callbacks.onUserLeft?.(data);
        });

        // Store channel reference
        this.channels.set(channelUuid, {
            channel,
            callbacks,
            channelUuid
        });

        return {
            channelUuid,
            channel,
            leave: () => this.leaveChannel(channelUuid),
            sendTyping: () => this.sendTyping(channelUuid),
            stopTyping: () => this.stopTyping(channelUuid)
        };
    }

    /**
     * Join a presence channel for online users
     * 
     * @param {string} channelUuid - The channel UUID
     * @param {Object} callbacks - Event callbacks
     * @returns {Object} Presence channel object
     */
    joinPresenceChannel(channelUuid, callbacks = {}) {
        const channelName = `presence-presence.channel.${channelUuid}`;
        
        const channel = this.pusher.subscribe(channelName);
        
        // Presence events
        channel.bind('pusher:subscription_succeeded', (members) => {
            console.log('Presence subscription succeeded:', members);
            callbacks.onSubscriptionSucceeded?.(members);
        });

        channel.bind('pusher:member_added', (member) => {
            console.log('Member added to presence:', member);
            callbacks.onMemberAdded?.(member);
        });

        channel.bind('pusher:member_removed', (member) => {
            console.log('Member removed from presence:', member);
            callbacks.onMemberRemoved?.(member);
        });

        return {
            channelUuid,
            channel,
            leave: () => this.leaveChannel(channelUuid)
        };
    }

    /**
     * Leave a channel
     * 
     * @param {string} channelUuid - The channel UUID
     */
    leaveChannel(channelUuid) {
        const channelData = this.channels.get(channelUuid);
        
        if (channelData) {
            this.pusher.unsubscribe(`private-channel.${channelUuid}`);
            this.pusher.unsubscribe(`presence-presence.channel.${channelUuid}`);
            this.channels.delete(channelUuid);
            console.log(`Left channel ${channelUuid}`);
        }
    }

    /**
     * Send typing indicator
     * 
     * @param {string} channelUuid - The channel UUID
     */
    sendTyping(channelUuid) {
        const channelData = this.channels.get(channelUuid);
        
        if (!channelData) {
            console.warn(`Not connected to channel ${channelUuid}`);
            return;
        }

        // Clear existing timeout
        if (this.typingTimeouts.has(channelUuid)) {
            clearTimeout(this.typingTimeouts.get(channelUuid));
        }

        // Send typing event
        channelData.channel.whisper('typing', {
            user: this.config.user,
            timestamp: Date.now()
        });

        // Auto-stop typing after 3 seconds
        const timeout = setTimeout(() => {
            this.stopTyping(channelUuid);
        }, 3000);

        this.typingTimeouts.set(channelUuid, timeout);
    }

    /**
     * Stop typing indicator
     * 
     * @param {string} channelUuid - The channel UUID
     */
    stopTyping(channelUuid) {
        const channelData = this.channels.get(channelUuid);
        
        if (!channelData) {
            return;
        }

        // Clear timeout
        if (this.typingTimeouts.has(channelUuid)) {
            clearTimeout(this.typingTimeouts.get(channelUuid));
            this.typingTimeouts.delete(channelUuid);
        }

        // Send stop typing event
        channelData.channel.whisper('typing-stopped', {
            user: this.config.user,
            timestamp: Date.now()
        });
    }

    /**
     * Send a message (this would typically go through your API)
     * 
     * @param {string} channelUuid - The channel UUID
     * @param {Object} messageData - Message data
     * @returns {Promise} API response
     */
    async sendMessage(channelUuid, messageData) {
        try {
            const response = await fetch(`${this.config.apiUrl}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.config.token}`,
                    'X-Public-Key': this.config.publicKey,
                    'Origin': this.config.origin,
                },
                body: JSON.stringify({
                    channel_uuid: channelUuid,
                    ...messageData
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Failed to send message:', error);
            throw error;
        }
    }

    /**
     * Get connection state
     * 
     * @returns {string} Connection state
     */
    getConnectionState() {
        return this.connectionState;
    }

    /**
     * Disconnect from all channels
     */
    disconnect() {
        this.channels.clear();
        this.typingTimeouts.clear();
        this.pusher.disconnect();
        this.connectionState = 'disconnected';
    }

    /**
     * Reconnect to Pusher
     */
    reconnect() {
        this.pusher.connect();
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SlimeTalksRealtime;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.SlimeTalksRealtime = SlimeTalksRealtime;
}
