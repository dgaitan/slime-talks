# Slime Talks Messaging API - Integration Guide

This guide provides comprehensive examples and best practices for integrating with the Slime Talks Messaging API.

## Table of Contents

- [Quick Start](#quick-start)
- [Authentication Setup](#authentication-setup)
- [SDK Examples](#sdk-examples)
- [Common Integration Patterns](#common-integration-patterns)
- [Error Handling](#error-handling)
- [Testing Your Integration](#testing-your-integration)
- [Production Deployment](#production-deployment)
- [Troubleshooting](#troubleshooting)

## Quick Start

### 1. Get Your API Credentials

Before you can use the API, you need to obtain your credentials:

```bash
# Create a client (this is typically done through your dashboard)
curl -X POST https://api.slime-talks.com/api/v1/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Application",
    "domain": "myapp.com",
    "public_key": "pk_live_your_public_key_here"
  }'
```

### 2. Set Up Authentication

All API requests require three headers:

```http
Authorization: Bearer sk_live_your_secret_key_here
X-Public-Key: pk_live_your_public_key_here
Origin: https://myapp.com
```

### 3. Make Your First Request

```bash
# Create a customer
curl -X POST https://api.slime-talks.com/api/v1/customers \
  -H "Authorization: Bearer sk_live_your_secret_key_here" \
  -H "X-Public-Key: pk_live_your_public_key_here" \
  -H "Origin: https://myapp.com" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com"
  }'
```

## Authentication Setup

### Environment Variables

Set up your environment variables:

```bash
# .env
SLIME_TALKS_API_URL=https://api.slime-talks.com/api/v1
SLIME_TALKS_SECRET_KEY=sk_live_your_secret_key_here
SLIME_TALKS_PUBLIC_KEY=pk_live_your_public_key_here
SLIME_TALKS_ORIGIN=https://myapp.com
```

### API Client Configuration

#### JavaScript/Node.js

```javascript
class SlimeTalksAPI {
  constructor(config) {
    this.baseURL = config.baseURL;
    this.secretKey = config.secretKey;
    this.publicKey = config.publicKey;
    this.origin = config.origin;
  }

  async request(method, endpoint, data = null) {
    const url = `${this.baseURL}${endpoint}`;
    const options = {
      method,
      headers: {
        'Authorization': `Bearer ${this.secretKey}`,
        'X-Public-Key': this.publicKey,
        'Origin': this.origin,
        'Content-Type': 'application/json',
      },
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || `HTTP ${response.status}`);
    }

    return response.json();
  }

  // Customer methods
  async createCustomer(data) {
    return this.request('POST', '/customers', data);
  }

  async getCustomer(customerId) {
    return this.request('GET', `/customers/${customerId}`);
  }

  async listCustomers(params = {}) {
    const query = new URLSearchParams(params).toString();
    return this.request('GET', `/customers?${query}`);
  }

  // Channel methods
  async createChannel(data) {
    return this.request('POST', '/channels', data);
  }

  async getChannel(channelId) {
    return this.request('GET', `/channels/${channelId}`);
  }

  async listChannels(params = {}) {
    const query = new URLSearchParams(params).toString();
    return this.request('GET', `/channels?${query}`);
  }

  async getCustomerChannels(customerId) {
    return this.request('GET', `/channels/customer/${customerId}`);
  }

  // Message methods
  async sendMessage(data) {
    return this.request('POST', '/messages', data);
  }

  async getChannelMessages(channelId, params = {}) {
    const query = new URLSearchParams(params).toString();
    return this.request('GET', `/messages/channel/${channelId}?${query}`);
  }

  async getCustomerMessages(customerId, params = {}) {
    const query = new URLSearchParams(params).toString();
    return this.request('GET', `/messages/customer/${customerId}?${query}`);
  }
}

// Usage
const api = new SlimeTalksAPI({
  baseURL: process.env.SLIME_TALKS_API_URL,
  secretKey: process.env.SLIME_TALKS_SECRET_KEY,
  publicKey: process.env.SLIME_TALKS_PUBLIC_KEY,
  origin: process.env.SLIME_TALKS_ORIGIN,
});
```

#### PHP

```php
<?php

class SlimeTalksAPI
{
    private string $baseURL;
    private string $secretKey;
    private string $publicKey;
    private string $origin;

    public function __construct(array $config)
    {
        $this->baseURL = $config['baseURL'];
        $this->secretKey = $config['secretKey'];
        $this->publicKey = $config['publicKey'];
        $this->origin = $config['origin'];
    }

    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseURL . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'X-Public-Key: ' . $this->publicKey,
            'Origin: ' . $this->origin,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new Exception($decoded['error'] ?? "HTTP $httpCode");
        }

        return $decoded;
    }

    // Customer methods
    public function createCustomer(array $data): array
    {
        return $this->request('POST', '/customers', $data);
    }

    public function getCustomer(string $customerId): array
    {
        return $this->request('GET', "/customers/$customerId");
    }

    public function listCustomers(array $params = []): array
    {
        $query = http_build_query($params);
        return $this->request('GET', "/customers?$query");
    }

    // Channel methods
    public function createChannel(array $data): array
    {
        return $this->request('POST', '/channels', $data);
    }

    public function getChannel(string $channelId): array
    {
        return $this->request('GET', "/channels/$channelId");
    }

    public function listChannels(array $params = []): array
    {
        $query = http_build_query($params);
        return $this->request('GET', "/channels?$query");
    }

    public function getCustomerChannels(string $customerId): array
    {
        return $this->request('GET', "/channels/customer/$customerId");
    }

    // Message methods
    public function sendMessage(array $data): array
    {
        return $this->request('POST', '/messages', $data);
    }

    public function getChannelMessages(string $channelId, array $params = []): array
    {
        $query = http_build_query($params);
        return $this->request('GET', "/messages/channel/$channelId?$query");
    }

    public function getCustomerMessages(string $customerId, array $params = []): array
    {
        $query = http_build_query($params);
        return $this->request('GET', "/messages/customer/$customerId?$query");
    }
}

// Usage
$api = new SlimeTalksAPI([
    'baseURL' => $_ENV['SLIME_TALKS_API_URL'],
    'secretKey' => $_ENV['SLIME_TALKS_SECRET_KEY'],
    'publicKey' => $_ENV['SLIME_TALKS_PUBLIC_KEY'],
    'origin' => $_ENV['SLIME_TALKS_ORIGIN'],
]);
```

#### Python

```python
import requests
import os
from typing import Dict, List, Optional

class SlimeTalksAPI:
    def __init__(self, config: Dict[str, str]):
        self.base_url = config['baseURL']
        self.secret_key = config['secretKey']
        self.public_key = config['publicKey']
        self.origin = config['origin']
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {self.secret_key}',
            'X-Public-Key': self.public_key,
            'Origin': self.origin,
            'Content-Type': 'application/json',
        })

    def request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Dict:
        url = f"{self.base_url}{endpoint}"
        
        try:
            response = self.session.request(method, url, json=data)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.HTTPError as e:
            error_data = response.json() if response.content else {}
            raise Exception(error_data.get('error', f'HTTP {response.status_code}'))

    # Customer methods
    def create_customer(self, data: Dict) -> Dict:
        return self.request('POST', '/customers', data)

    def get_customer(self, customer_id: str) -> Dict:
        return self.request('GET', f'/customers/{customer_id}')

    def list_customers(self, params: Optional[Dict] = None) -> Dict:
        query = '&'.join([f'{k}={v}' for k, v in (params or {}).items()])
        return self.request('GET', f'/customers?{query}')

    # Channel methods
    def create_channel(self, data: Dict) -> Dict:
        return self.request('POST', '/channels', data)

    def get_channel(self, channel_id: str) -> Dict:
        return self.request('GET', f'/channels/{channel_id}')

    def list_channels(self, params: Optional[Dict] = None) -> Dict:
        query = '&'.join([f'{k}={v}' for k, v in (params or {}).items()])
        return self.request('GET', f'/channels?{query}')

    def get_customer_channels(self, customer_id: str) -> Dict:
        return self.request('GET', f'/channels/customer/{customer_id}')

    # Message methods
    def send_message(self, data: Dict) -> Dict:
        return self.request('POST', '/messages', data)

    def get_channel_messages(self, channel_id: str, params: Optional[Dict] = None) -> Dict:
        query = '&'.join([f'{k}={v}' for k, v in (params or {}).items()])
        return self.request('GET', f'/messages/channel/{channel_id}?{query}')

    def get_customer_messages(self, customer_id: str, params: Optional[Dict] = None) -> Dict:
        query = '&'.join([f'{k}={v}' for k, v in (params or {}).items()])
        return self.request('GET', f'/messages/customer/{customer_id}?{query}')

# Usage
api = SlimeTalksAPI({
    'baseURL': os.getenv('SLIME_TALKS_API_URL'),
    'secretKey': os.getenv('SLIME_TALKS_SECRET_KEY'),
    'publicKey': os.getenv('SLIME_TALKS_PUBLIC_KEY'),
    'origin': os.getenv('SLIME_TALKS_ORIGIN'),
})
```

## Common Integration Patterns

### 1. Customer Onboarding Flow

```javascript
// Complete customer onboarding
async function onboardCustomer(userData) {
  try {
    // 1. Create customer
    const customer = await api.createCustomer({
      name: userData.name,
      email: userData.email,
      metadata: {
        source: 'web_app',
        plan: 'premium'
      }
    });

    // 2. Create welcome channel
    const channel = await api.createChannel({
      type: 'custom',
      name: 'Welcome Channel',
      customer_uuids: [customer.id]
    });

    // 3. Send welcome message
    await api.sendMessage({
      channel_uuid: channel.id,
      sender_uuid: customer.id,
      type: 'text',
      content: 'Welcome to our platform!',
      metadata: {
        type: 'welcome',
        priority: 'high'
      }
    });

    return { customer, channel };
  } catch (error) {
    console.error('Onboarding failed:', error);
    throw error;
  }
}
```

### 2. Support Ticket System

```javascript
// Create support ticket channel
async function createSupportTicket(customerId, issue) {
  try {
    // Create support channel
    const channel = await api.createChannel({
      type: 'custom',
      name: `Support Ticket #${Date.now()}`,
      customer_uuids: [customerId]
    });

    // Send initial message
    await api.sendMessage({
      channel_uuid: channel.id,
      sender_uuid: customerId,
      type: 'text',
      content: issue.description,
      metadata: {
        priority: issue.priority,
        category: issue.category,
        ticket_id: channel.id
      }
    });

    return channel;
  } catch (error) {
    console.error('Support ticket creation failed:', error);
    throw error;
  }
}
```

### 3. Real-time Messaging

```javascript
// Poll for new messages
class MessagePoller {
  constructor(api, channelId, onNewMessage) {
    this.api = api;
    this.channelId = channelId;
    this.onNewMessage = onNewMessage;
    this.lastMessageId = null;
    this.isPolling = false;
  }

  async start() {
    this.isPolling = true;
    await this.poll();
  }

  stop() {
    this.isPolling = false;
  }

  async poll() {
    if (!this.isPolling) return;

    try {
      const params = { limit: 10 };
      if (this.lastMessageId) {
        params.starting_after = this.lastMessageId;
      }

      const response = await this.api.getChannelMessages(this.channelId, params);
      
      if (response.data.length > 0) {
        this.lastMessageId = response.data[response.data.length - 1].id;
        response.data.forEach(message => this.onNewMessage(message));
      }

      // Poll again in 2 seconds
      setTimeout(() => this.poll(), 2000);
    } catch (error) {
      console.error('Polling error:', error);
      setTimeout(() => this.poll(), 5000); // Retry in 5 seconds
    }
  }
}

// Usage
const poller = new MessagePoller(api, channelId, (message) => {
  console.log('New message:', message);
  // Update UI with new message
});

poller.start();
```

### 4. Message History with Pagination

```javascript
// Load message history with pagination
async function loadMessageHistory(channelId, limit = 50) {
  const allMessages = [];
  let hasMore = true;
  let startingAfter = null;

  while (hasMore) {
    try {
      const params = { limit };
      if (startingAfter) {
        params.starting_after = startingAfter;
      }

      const response = await api.getChannelMessages(channelId, params);
      
      allMessages.push(...response.data);
      hasMore = response.has_more;
      startingAfter = response.data[response.data.length - 1]?.id;
    } catch (error) {
      console.error('Failed to load message history:', error);
      break;
    }
  }

  return allMessages;
}
```

## Error Handling

### Common Error Scenarios

```javascript
async function handleAPIError(error) {
  if (error.message.includes('Unauthorized')) {
    // Handle authentication errors
    console.error('Authentication failed:', error.message);
    // Redirect to login or refresh token
  } else if (error.message.includes('not found')) {
    // Handle not found errors
    console.error('Resource not found:', error.message);
    // Show appropriate UI message
  } else if (error.message.includes('validation')) {
    // Handle validation errors
    console.error('Validation failed:', error.message);
    // Show field-specific errors
  } else {
    // Handle other errors
    console.error('API error:', error.message);
    // Show generic error message
  }
}

// Usage with try-catch
try {
  const customer = await api.createCustomer(customerData);
} catch (error) {
  await handleAPIError(error);
}
```

### Retry Logic

```javascript
async function apiRequestWithRetry(requestFn, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      return await requestFn();
    } catch (error) {
      if (attempt === maxRetries) {
        throw error;
      }
      
      // Wait before retry (exponential backoff)
      const delay = Math.pow(2, attempt) * 1000;
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
}

// Usage
const customer = await apiRequestWithRetry(() => 
  api.createCustomer(customerData)
);
```

## Testing Your Integration

### Unit Tests

```javascript
// Jest test example
describe('SlimeTalksAPI', () => {
  let api;

  beforeEach(() => {
    api = new SlimeTalksAPI({
      baseURL: 'https://api.slime-talks.com/api/v1',
      secretKey: 'sk_test_1234567890',
      publicKey: 'pk_test_1234567890',
      origin: 'https://test.com'
    });
  });

  test('should create customer', async () => {
    const customerData = {
      name: 'Test User',
      email: 'test@example.com'
    };

    const customer = await api.createCustomer(customerData);
    
    expect(customer.object).toBe('customer');
    expect(customer.name).toBe('Test User');
    expect(customer.email).toBe('test@example.com');
  });

  test('should handle authentication errors', async () => {
    const invalidApi = new SlimeTalksAPI({
      baseURL: 'https://api.slime-talks.com/api/v1',
      secretKey: 'invalid_key',
      publicKey: 'pk_test_1234567890',
      origin: 'https://test.com'
    });

    await expect(invalidApi.createCustomer({}))
      .rejects.toThrow('Unauthorized');
  });
});
```

### Integration Tests

```javascript
// Full integration test
describe('Messaging Flow', () => {
  test('complete messaging workflow', async () => {
    // 1. Create customers
    const customer1 = await api.createCustomer({
      name: 'User 1',
      email: 'user1@example.com'
    });

    const customer2 = await api.createCustomer({
      name: 'User 2',
      email: 'user2@example.com'
    });

    // 2. Create channel
    const channel = await api.createChannel({
      type: 'general',
      customer_uuids: [customer1.id, customer2.id]
    });

    // 3. Send message
    const message = await api.sendMessage({
      channel_uuid: channel.id,
      sender_uuid: customer1.id,
      type: 'text',
      content: 'Hello!'
    });

    // 4. Retrieve messages
    const messages = await api.getChannelMessages(channel.id);
    
    expect(messages.data).toHaveLength(1);
    expect(messages.data[0].content).toBe('Hello!');
  });
});
```

## Production Deployment

### Environment Configuration

```bash
# Production environment variables
SLIME_TALKS_API_URL=https://api.slime-talks.com/api/v1
SLIME_TALKS_SECRET_KEY=sk_live_your_live_secret_key
SLIME_TALKS_PUBLIC_KEY=pk_live_your_live_public_key
SLIME_TALKS_ORIGIN=https://yourdomain.com
```

### Security Best Practices

1. **Never expose secret keys** in client-side code
2. **Use environment variables** for configuration
3. **Implement proper CORS** settings
4. **Validate all inputs** before sending to API
5. **Implement rate limiting** on your side
6. **Use HTTPS** for all API communications

### Monitoring and Logging

```javascript
// Add logging to your API client
class LoggingSlimeTalksAPI extends SlimeTalksAPI {
  async request(method, endpoint, data = null) {
    const startTime = Date.now();
    
    try {
      const result = await super.request(method, endpoint, data);
      
      console.log(`API Success: ${method} ${endpoint} (${Date.now() - startTime}ms)`);
      return result;
    } catch (error) {
      console.error(`API Error: ${method} ${endpoint} (${Date.now() - startTime}ms)`, error);
      throw error;
    }
  }
}
```

## Troubleshooting

### Common Issues

1. **Authentication Errors**
   - Verify your secret key and public key
   - Check that the Origin header matches your registered domain
   - Ensure the Authorization header format is correct

2. **CORS Issues**
   - Make sure your domain is registered with the API
   - Check that you're using HTTPS in production

3. **Rate Limiting**
   - Implement exponential backoff for retries
   - Consider caching frequently accessed data

4. **Pagination Issues**
   - Remember that `starting_after` uses the last item's ID from the previous page
   - Check that you're handling the `has_more` flag correctly

### Debug Mode

```javascript
// Enable debug logging
const api = new SlimeTalksAPI({
  baseURL: process.env.SLIME_TALKS_API_URL,
  secretKey: process.env.SLIME_TALKS_SECRET_KEY,
  publicKey: process.env.SLIME_TALKS_PUBLIC_KEY,
  origin: process.env.SLIME_TALKS_ORIGIN,
  debug: true // Enable debug mode
});
```

### Support

If you encounter issues:

1. Check the [API Documentation](./API_DOCUMENTATION.md)
2. Review the [Swagger Specification](./swagger.yaml)
3. Test with the provided examples
4. Contact support with specific error messages and request details

---

**Happy integrating! 🚀**
