/**
 * Slime Talks JavaScript SDK
 * 
 * A comprehensive JavaScript SDK for the Slime Talks Messaging API.
 * Includes both REST API client and real-time messaging support via Pusher.
 * 
 * @package SlimeTalks\SDK\JavaScript
 * @author Laravel Slime Talks
 * @version 1.0.0
 */

class SlimeTalksSDK {
    /**
     * Create a new Slime Talks SDK instance
     * 
     * @param {Object} config Configuration object
     * @param {string} config.apiUrl - API base URL
     * @param {string} config.secretKey - API secret key
     * @param {string} config.publicKey - API public key
     * @param {string} config.origin - Origin domain
     * @param {string} [config.pusherKey] - Pusher key for real-time features
     * @param {string} [config.pusherCluster] - Pusher cluster
     * @param {number} [config.timeout=30000] - Request timeout in milliseconds
     */
    constructor(config) {
        this.config = {
            apiUrl: config.apiUrl.replace(/\/$/, ''),
            secretKey: config.secretKey,
            publicKey: config.publicKey,
            origin: config.origin,
            pusherKey: config.pusherKey,
            pusherCluster: config.pusherCluster || 'us2',
            timeout: config.timeout || 30000,
        };

        this.realtime = null;
    }

    /**
     * Initialize real-time messaging
     * 
     * @param {Object} user - Current user information
     * @param {string} user.id - User UUID
     * @param {string} user.name - User name
     * @returns {SlimeTalksRealtime} Real-time client instance
     */
    initRealtime(user) {
        if (!this.config.pusherKey) {
            throw new Error('Pusher key is required for real-time features');
        }

        if (!window.SlimeTalksRealtime) {
            throw new Error('SlimeTalksRealtime client is not loaded. Please include slime-talks-realtime.js');
        }

        this.realtime = new SlimeTalksRealtime({
            apiUrl: this.config.apiUrl,
            pusherKey: this.config.pusherKey,
            pusherCluster: this.config.pusherCluster,
            token: this.config.secretKey,
            publicKey: this.config.publicKey,
            origin: this.config.origin,
            user: user,
        });

        return this.realtime;
    }

    /**
     * Get the real-time client
     * 
     * @returns {SlimeTalksRealtime|null} Real-time client instance
     */
    getRealtime() {
        return this.realtime;
    }

    // ==================== Client Management ====================

    /**
     * Get client information
     * 
     * @param {string} clientUuid - Client UUID
     * @returns {Promise<Object>} Client data
     */
    async getClient(clientUuid) {
        return this._request('GET', `/client/${clientUuid}`);
    }

    // ==================== Customer Management ====================

    /**
     * Create a new customer
     * 
     * @param {Object} data - Customer data
     * @param {string} data.name - Customer name
     * @param {string} data.email - Customer email
     * @param {Object} [data.metadata] - Additional metadata
     * @returns {Promise<Object>} Created customer
     */
    async createCustomer(data) {
        return this._request('POST', '/customers', data);
    }

    /**
     * Get customer by UUID
     * 
     * @param {string} customerUuid - Customer UUID
     * @returns {Promise<Object>} Customer data
     */
    async getCustomer(customerUuid) {
        return this._request('GET', `/customers/${customerUuid}`);
    }

    /**
     * List customers with pagination
     * 
     * @param {Object} [params] - Query parameters
     * @param {number} [params.limit] - Number of items per page
     * @param {string} [params.starting_after] - UUID to start after
     * @returns {Promise<Object>} Paginated customers
     */
    async listCustomers(params = {}) {
        const query = new URLSearchParams(params).toString();
        const endpoint = `/customers${query ? `?${query}` : ''}`;
        return this._request('GET', endpoint);
    }

    // ==================== Channel Management ====================

    /**
     * Create a channel
     * 
     * @param {Object} data - Channel data
     * @param {string} data.type - Channel type ('general' or 'custom')
     * @param {string[]} data.customer_uuids - Array of customer UUIDs
     * @param {string} [data.name] - Channel name (required for custom channels)
     * @returns {Promise<Object>} Created channel
     */
    async createChannel(data) {
        return this._request('POST', '/channels', data);
    }

    /**
     * Get channel by UUID
     * 
     * @param {string} channelUuid - Channel UUID
     * @returns {Promise<Object>} Channel data
     */
    async getChannel(channelUuid) {
        return this._request('GET', `/channels/${channelUuid}`);
    }

    /**
     * List channels with pagination
     * 
     * @param {Object} [params] - Query parameters
     * @param {number} [params.limit] - Number of items per page
     * @param {string} [params.starting_after] - UUID to start after
     * @returns {Promise<Object>} Paginated channels
     */
    async listChannels(params = {}) {
        const query = new URLSearchParams(params).toString();
        const endpoint = `/channels${query ? `?${query}` : ''}`;
        return this._request('GET', endpoint);
    }

    /**
     * Get channels for a specific customer
     * 
     * @param {string} customerUuid - Customer UUID
     * @returns {Promise<Object>} Customer's channels
     */
    async getCustomerChannels(customerUuid) {
        return this._request('GET', `/channels/customer/${customerUuid}`);
    }

    // ==================== Message Management ====================

    /**
     * Send a message to a channel
     * 
     * @param {Object} data - Message data
     * @param {string} data.channel_uuid - Channel UUID
     * @param {string} data.sender_uuid - Sender UUID
     * @param {string} data.type - Message type ('text', 'image', 'file')
     * @param {string} data.content - Message content
     * @param {Object} [data.metadata] - Additional metadata
     * @returns {Promise<Object>} Sent message
     */
    async sendMessage(data) {
        return this._request('POST', '/messages', data);
    }

    /**
     * Get messages from a channel
     * 
     * @param {string} channelUuid - Channel UUID
     * @param {Object} [params] - Query parameters
     * @param {number} [params.limit] - Number of items per page
     * @param {string} [params.starting_after] - UUID to start after
     * @returns {Promise<Object>} Paginated messages
     */
    async getChannelMessages(channelUuid, params = {}) {
        const query = new URLSearchParams(params).toString();
        const endpoint = `/messages/channel/${channelUuid}${query ? `?${query}` : ''}`;
        return this._request('GET', endpoint);
    }

    /**
     * Get messages for a specific customer
     * 
     * @param {string} customerUuid - Customer UUID
     * @param {Object} [params] - Query parameters
     * @param {number} [params.limit] - Number of items per page
     * @param {string} [params.starting_after] - UUID to start after
     * @returns {Promise<Object>} Paginated messages
     */
    async getCustomerMessages(customerUuid, params = {}) {
        const query = new URLSearchParams(params).toString();
        const endpoint = `/messages/customer/${customerUuid}${query ? `?${query}` : ''}`;
        return this._request('GET', endpoint);
    }

    // ==================== Private Methods ====================

    /**
     * Make an HTTP request to the API
     * 
     * @private
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} [data] - Request data
     * @returns {Promise<Object>} Response data
     * @throws {SlimeTalksError} When request fails
     */
    async _request(method, endpoint, data = null) {
        const url = `${this.config.apiUrl}${endpoint}`;
        const options = {
            method: method,
            headers: {
                'Authorization': `Bearer ${this.config.secretKey}`,
                'X-Public-Key': this.config.publicKey,
                'Origin': this.config.origin,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
        options.signal = controller.signal;

        try {
            const response = await fetch(url, options);
            clearTimeout(timeoutId);

            const responseData = await response.json();

            if (!response.ok) {
                throw new SlimeTalksError(
                    this._parseErrorMessage(responseData),
                    response.status,
                    responseData
                );
            }

            return responseData;
        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === 'AbortError') {
                throw new SlimeTalksError('Request timeout', 408);
            }

            if (error instanceof SlimeTalksError) {
                throw error;
            }

            throw new SlimeTalksError(
                `Network error: ${error.message}`,
                0,
                error
            );
        }
    }

    /**
     * Parse error message from response
     * 
     * @private
     * @param {Object} responseData - Response data
     * @returns {string} Error message
     */
    _parseErrorMessage(responseData) {
        if (responseData.error?.message) {
            return responseData.error.message;
        }

        if (typeof responseData.error === 'string') {
            return responseData.error;
        }

        if (responseData.message) {
            return responseData.message;
        }

        return 'An unknown error occurred';
    }
}

/**
 * Slime Talks Error
 * 
 * Custom error class for API errors
 */
class SlimeTalksError extends Error {
    /**
     * Create a new error instance
     * 
     * @param {string} message - Error message
     * @param {number} status - HTTP status code
     * @param {Object} [data] - Additional error data
     */
    constructor(message, status, data = null) {
        super(message);
        this.name = 'SlimeTalksError';
        this.status = status;
        this.data = data;
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SlimeTalksSDK, SlimeTalksError };
}

// Make available globally
if (typeof window !== 'undefined') {
    window.SlimeTalksSDK = SlimeTalksSDK;
    window.SlimeTalksError = SlimeTalksError;
}
