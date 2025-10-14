/**
 * Customer-Centric Messaging Example
 * 
 * This example demonstrates how to use the Slime Talks SDK for customer-centric messaging,
 * similar to WhatsApp, Slack, or other messaging applications.
 * 
 * @package SlimeTalks\SDK\JavaScript\Examples
 * @author Laravel Slime Talks
 * @version 1.0.0
 */

class CustomerMessagingApp {
    constructor(sdk) {
        this.sdk = sdk;
        this.currentUser = null;
        this.selectedCustomer = null;
        this.realtime = null;
    }

    /**
     * Initialize the messaging app
     * 
     * @param {Object} user - Current user information
     * @param {string} user.id - User UUID
     * @param {string} user.name - User name
     * @param {string} user.email - User email
     */
    async init(user) {
        this.currentUser = user;
        
        // Initialize real-time messaging
        this.realtime = this.sdk.initRealtime(user);
        
        // Load the sidebar with active customers
        await this.loadActiveCustomers();
        
        // Set up real-time event handlers
        this.setupRealtimeHandlers();
        
        console.log('Customer messaging app initialized');
    }

    /**
     * Load active customers for the sidebar
     */
    async loadActiveCustomers() {
        try {
            const response = await this.sdk.getActiveCustomers({ limit: 50 });
            const customers = response.data;
            
            console.log(`Loaded ${customers.length} active customers`);
            
            // Update sidebar UI
            this.updateSidebar(customers);
            
        } catch (error) {
            console.error('Failed to load active customers:', error);
            this.showError('Failed to load customers');
        }
    }

    /**
     * Update the sidebar with active customers
     * 
     * @param {Array} customers - Array of customer objects
     */
    updateSidebar(customers) {
        const sidebar = document.getElementById('customers-sidebar');
        if (!sidebar) return;

        sidebar.innerHTML = '';

        customers.forEach(customer => {
            const customerElement = this.createCustomerElement(customer);
            customerElement.addEventListener('click', () => {
                this.selectCustomer(customer);
            });
            sidebar.appendChild(customerElement);
        });
    }

    /**
     * Create a customer element for the sidebar
     * 
     * @param {Object} customer - Customer object
     * @returns {HTMLElement} Customer element
     */
    createCustomerElement(customer) {
        const div = document.createElement('div');
        div.className = 'customer-item';
        div.dataset.customerEmail = customer.email;
        
        const avatar = document.createElement('div');
        avatar.className = 'customer-avatar';
        avatar.textContent = customer.name.charAt(0).toUpperCase();
        
        const info = document.createElement('div');
        info.className = 'customer-info';
        
        const name = document.createElement('div');
        name.className = 'customer-name';
        name.textContent = customer.name;
        
        const email = document.createElement('div');
        email.className = 'customer-email';
        email.textContent = customer.email;
        
        const lastMessage = document.createElement('div');
        lastMessage.className = 'customer-last-message';
        if (customer.latest_message_at) {
            lastMessage.textContent = this.formatTimestamp(customer.latest_message_at);
        }
        
        info.appendChild(name);
        info.appendChild(email);
        info.appendChild(lastMessage);
        
        div.appendChild(avatar);
        div.appendChild(info);
        
        return div;
    }

    /**
     * Select a customer and load their conversation
     * 
     * @param {Object} customer - Selected customer
     */
    async selectCustomer(customer) {
        this.selectedCustomer = customer;
        
        // Update UI to show selected customer
        this.updateSelectedCustomer(customer);
        
        // Load conversation with this customer
        await this.loadConversation(customer.email);
        
        // Join real-time channel for this conversation
        this.joinConversationChannel(customer.email);
    }

    /**
     * Update UI to show the selected customer
     * 
     * @param {Object} customer - Selected customer
     */
    updateSelectedCustomer(customer) {
        // Remove previous selection
        document.querySelectorAll('.customer-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Add selection to current customer
        const customerElement = document.querySelector(`[data-customer-email="${customer.email}"]`);
        if (customerElement) {
            customerElement.classList.add('selected');
        }
        
        // Update header
        const header = document.getElementById('conversation-header');
        if (header) {
            header.innerHTML = `
                <div class="conversation-title">${customer.name}</div>
                <div class="conversation-subtitle">${customer.email}</div>
            `;
        }
    }

    /**
     * Load conversation between current user and selected customer
     * 
     * @param {string} customerEmail - Customer email
     */
    async loadConversation(customerEmail) {
        try {
            const response = await this.sdk.getMessagesBetweenCustomers(
                this.currentUser.email,
                customerEmail,
                { limit: 50 }
            );
            
            const messages = response.data;
            console.log(`Loaded ${messages.length} messages`);
            
            // Update messages area
            this.updateMessagesArea(messages);
            
        } catch (error) {
            console.error('Failed to load conversation:', error);
            this.showError('Failed to load conversation');
        }
    }

    /**
     * Update the messages area with conversation messages
     * 
     * @param {Array} messages - Array of message objects
     */
    updateMessagesArea(messages) {
        const messagesArea = document.getElementById('messages-area');
        if (!messagesArea) return;

        messagesArea.innerHTML = '';

        messages.forEach(message => {
            const messageElement = this.createMessageElement(message);
            messagesArea.appendChild(messageElement);
        });

        // Scroll to bottom
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    /**
     * Create a message element
     * 
     * @param {Object} message - Message object
     * @returns {HTMLElement} Message element
     */
    createMessageElement(message) {
        const div = document.createElement('div');
        div.className = 'message';
        
        const isOwnMessage = message.sender_id === this.currentUser.id;
        div.classList.add(isOwnMessage ? 'own-message' : 'other-message');
        
        const content = document.createElement('div');
        content.className = 'message-content';
        content.textContent = message.content;
        
        const meta = document.createElement('div');
        meta.className = 'message-meta';
        
        const timestamp = document.createElement('span');
        timestamp.className = 'message-timestamp';
        timestamp.textContent = this.formatTimestamp(message.created);
        
        meta.appendChild(timestamp);
        
        div.appendChild(content);
        div.appendChild(meta);
        
        return div;
    }

    /**
     * Join real-time channel for the current conversation
     * 
     * @param {string} customerEmail - Customer email
     */
    joinConversationChannel(customerEmail) {
        if (!this.realtime) return;

        // Get channels for this customer
        this.sdk.getChannelsByEmail(customerEmail).then(response => {
            const conversations = response.data.conversations;
            
            // Find the conversation with the current user
            const conversation = conversations.find(conv => 
                conv.recipient.email === this.currentUser.email ||
                conv.channels.some(channel => channel.type === 'general')
            );
            
            if (conversation && conversation.channels.length > 0) {
                const channel = conversation.channels[0]; // Use first channel (usually general)
                
                // Join the channel for real-time updates
                this.realtime.joinChannel(channel.id);
            }
        }).catch(error => {
            console.error('Failed to get channels for real-time:', error);
        });
    }

    /**
     * Send a message to the selected customer
     * 
     * @param {string} content - Message content
     */
    async sendMessage(content) {
        if (!this.selectedCustomer || !content.trim()) {
            return;
        }

        try {
            const message = await this.sdk.sendToCustomer({
                sender_email: this.currentUser.email,
                recipient_email: this.selectedCustomer.email,
                type: 'text',
                content: content.trim(),
            });

            console.log('Message sent:', message);
            
            // Add message to UI immediately (optimistic update)
            this.addMessageToUI(message);
            
            // Clear input
            const input = document.getElementById('message-input');
            if (input) {
                input.value = '';
            }

        } catch (error) {
            console.error('Failed to send message:', error);
            this.showError('Failed to send message');
        }
    }

    /**
     * Add a message to the UI
     * 
     * @param {Object} message - Message object
     */
    addMessageToUI(message) {
        const messagesArea = document.getElementById('messages-area');
        if (!messagesArea) return;

        const messageElement = this.createMessageElement(message);
        messagesArea.appendChild(messageElement);
        
        // Scroll to bottom
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    /**
     * Set up real-time event handlers
     */
    setupRealtimeHandlers() {
        if (!this.realtime) return;

        // Handle new messages
        this.realtime.on('message.sent', (data) => {
            console.log('Real-time message received:', data);
            
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

    /**
     * Show typing indicator
     * 
     * @param {Object} customer - Customer who is typing
     */
    showTypingIndicator(customer) {
        const messagesArea = document.getElementById('messages-area');
        if (!messagesArea) return;

        // Remove existing typing indicator
        const existing = document.getElementById('typing-indicator');
        if (existing) {
            existing.remove();
        }

        const indicator = document.createElement('div');
        indicator.id = 'typing-indicator';
        indicator.className = 'typing-indicator';
        indicator.innerHTML = `
            <div class="typing-dots">
                <span></span><span></span><span></span>
            </div>
            <span class="typing-text">${customer.name} is typing...</span>
        `;

        messagesArea.appendChild(indicator);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    /**
     * Hide typing indicator
     */
    hideTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Format timestamp for display
     * 
     * @param {number} timestamp - Unix timestamp
     * @returns {string} Formatted timestamp
     */
    formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) { // Less than 1 minute
            return 'Just now';
        } else if (diff < 3600000) { // Less than 1 hour
            return `${Math.floor(diff / 60000)}m ago`;
        } else if (diff < 86400000) { // Less than 1 day
            return `${Math.floor(diff / 3600000)}h ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    /**
     * Show error message
     * 
     * @param {string} message - Error message
     */
    showError(message) {
        console.error(message);
        // You can implement a toast notification or modal here
        alert(message);
    }

    /**
     * Start typing indicator
     */
    startTyping() {
        if (!this.selectedCustomer || !this.realtime) return;
        
        this.realtime.startTyping(this.selectedCustomer.id);
    }

    /**
     * Stop typing indicator
     */
    stopTyping() {
        if (!this.selectedCustomer || !this.realtime) return;
        
        this.realtime.stopTyping(this.selectedCustomer.id);
    }
}

// Example usage
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize SDK
    const sdk = new SlimeTalksSDK({
        apiUrl: 'https://your-api-domain.com/api/v1',
        secretKey: 'sk_test_1234567890abcdef',
        publicKey: 'pk_test_1234567890abcdef',
        origin: 'https://yourdomain.com',
        pusherKey: 'your-pusher-key',
        pusherCluster: 'us2',
    });

    // Initialize messaging app
    const app = new CustomerMessagingApp(sdk);
    
    // Mock current user (replace with actual user data)
    const currentUser = {
        id: 'cus_1234567890',
        name: 'John Doe',
        email: 'john@example.com'
    };
    
    await app.init(currentUser);

    // Set up message input
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    
    if (messageInput && sendButton) {
        sendButton.addEventListener('click', () => {
            app.sendMessage(messageInput.value);
        });
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                app.sendMessage(messageInput.value);
            }
        });
        
        // Typing indicators
        let typingTimeout;
        messageInput.addEventListener('input', () => {
            app.startTyping();
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                app.stopTyping();
            }, 1000);
        });
    }

    // Make app available globally for debugging
    window.messagingApp = app;
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CustomerMessagingApp };
}
