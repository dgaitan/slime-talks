# Slime Talks Messaging API - Client Documentation

**Version:** 1.0.0  
**Last Updated:** January 2025  
**Base URL:** `https://your-api-domain.com/api/v1`

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Base URL & Headers](#base-url--headers)
- [API Endpoints](#api-endpoints)
  - [Client Management](#client-management)
  - [Customer Management](#customer-management)
  - [Channel Management](#channel-management)
  - [Message Management](#message-management)
- [Response Formats](#response-formats)
- [Error Handling](#error-handling)
- [Pagination](#pagination)
- [Code Examples](#code-examples)
- [SDK Integration](#sdk-integration)

---

## Overview

The Slime Talks Messaging API is a comprehensive, multi-tenant messaging platform built with Laravel. It provides secure, scalable messaging capabilities with complete client isolation, authentication, and real-time features.

### Key Features

- ✅ **Multi-tenant Architecture**: Complete client isolation
- ✅ **Secure Authentication**: Bearer tokens + public key validation
- ✅ **Real-time Messaging**: WebSocket support with Pusher integration
- ✅ **Comprehensive Pagination**: Cursor-based pagination
- ✅ **Stripe-inspired API**: Consistent JSON responses
- ✅ **Production Ready**: Full test coverage (116 tests)

---

## Authentication

All API endpoints require the following headers:

| Header | Required | Description | Example |
|--------|----------|-------------|---------|
| `Authorization` | ✅ | Bearer token for authentication | `Bearer sk_test_1234567890abcdef` |
| `X-Public-Key` | ✅ | Client's public key for validation | `pk_test_1234567890abcdef` |
| `Origin` | ✅ | Client's registered domain | `https://yourdomain.com` |

### Example Request Headers

```http
Authorization: Bearer sk_test_1234567890abcdef
X-Public-Key: pk_test_1234567890abcdef
Origin: https://yourdomain.com
Content-Type: application/json
```

---

## Base URL & Headers

**Base URL:** `https://your-api-domain.com/api/v1`

All requests should include:
- `Content-Type: application/json` (for POST/PUT requests)
- Authentication headers (see above)

---

## API Endpoints

### Client Management

#### Get Client Information

**GET** `/client/{client_uuid}`

Retrieves information about a specific client.

**Response:**
```json
{
    "object": "client",
    "id": "clt_1234567890abcdef",
    "name": "Example Client",
    "domain": "example.com",
    "public_key": "pk_test_1234567890abcdef",
    "allowed_ips": ["127.0.0.1"],
    "allowed_subdomains": ["api", "app"],
    "created": 1640995200,
    "livemode": false
}
```

---

### Customer Management

#### Create Customer

**POST** `/customers`

Creates a new customer for the authenticated client.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {
        "department": "Engineering",
        "role": "Developer"
    }
}
```

**Response:**
```json
{
    "object": "customer",
    "id": "cus_1234567890abcdef",
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {
        "department": "Engineering",
        "role": "Developer"
    },
    "created": 1640995200,
    "livemode": false
}
```

#### Get Customer

**GET** `/customers/{customer_uuid}`

Retrieves customer information.

**Response:**
```json
{
    "object": "customer",
    "id": "cus_1234567890abcdef",
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {
        "department": "Engineering",
        "role": "Developer"
    },
    "created": 1640995200,
    "livemode": false
}
```

#### List Customers

**GET** `/customers`

Lists all customers for the authenticated client with pagination support.

**Query Parameters:**
- `limit` (optional): Number of customers per page (default: 10, max: 100)
- `starting_after` (optional): Customer UUID to start after for pagination

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "customer",
            "id": "cus_1234567890abcdef",
            "name": "John Doe",
            "email": "john@example.com",
            "metadata": {
                "department": "Engineering",
                "role": "Developer"
            },
            "created": 1640995200,
            "livemode": false
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

---

### Channel Management

#### Create General Channel

**POST** `/channels`

Creates a general (direct message) channel between customers.

**Request Body:**
```json
{
    "type": "general",
    "customer_uuids": ["cus_1234567890abcdef", "cus_0987654321fedcba"]
}
```

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890abcdef",
    "type": "general",
    "name": "general",
    "customers": [
        {
            "object": "customer",
            "id": "cus_1234567890abcdef",
            "name": "John Doe",
            "email": "john@example.com"
        },
        {
            "object": "customer",
            "id": "cus_0987654321fedcba",
            "name": "Jane Smith",
            "email": "jane@example.com"
        }
    ],
    "created": 1640995200,
    "livemode": false
}
```

#### Create Custom Channel

**POST** `/channels`

Creates a custom (topic-specific) channel. If a custom channel with the same name already exists, returns the existing channel.

**Request Body:**
```json
{
    "type": "custom",
    "name": "Engineering Team",
    "customer_uuids": ["cus_1234567890abcdef", "cus_0987654321fedcba", "cus_1122334455aabbcc"]
}
```

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890abcdef",
    "type": "custom",
    "name": "Engineering Team",
    "customers": [
        {
            "object": "customer",
            "id": "cus_1234567890abcdef",
            "name": "John Doe",
            "email": "john@example.com"
        },
        {
            "object": "customer",
            "id": "cus_0987654321fedcba",
            "name": "Jane Smith",
            "email": "jane@example.com"
        },
        {
            "object": "customer",
            "id": "cus_1122334455aabbcc",
            "name": "Bob Wilson",
            "email": "bob@example.com"
        }
    ],
    "created": 1640995200,
    "livemode": false
}
```

#### Get Channel

**GET** `/channels/{channel_uuid}`

Retrieves channel information.

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890abcdef",
    "type": "custom",
    "name": "Engineering Team",
    "customers": [
        {
            "object": "customer",
            "id": "cus_1234567890abcdef",
            "name": "John Doe",
            "email": "john@example.com"
        }
    ],
    "created": 1640995200,
    "livemode": false
}
```

#### List Channels

**GET** `/channels`

Lists all channels for the authenticated client.

**Query Parameters:**
- `limit` (optional): Number of channels per page (default: 10, max: 100)
- `starting_after` (optional): Channel UUID to start after for pagination

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_1234567890abcdef",
            "type": "custom",
            "name": "Engineering Team",
            "customers": [
                {
                    "object": "customer",
                    "id": "cus_1234567890abcdef",
                    "name": "John Doe",
                    "email": "john@example.com"
                }
            ],
            "created": 1640995200,
            "livemode": false
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

#### Get Customer Channels

**GET** `/channels/customer/{customer_uuid}`

Lists all channels where a specific customer participates.

**Query Parameters:**
- `limit` (optional): Number of channels per page (default: 10, max: 100)
- `starting_after` (optional): Channel UUID to start after for pagination

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_1234567890abcdef",
            "type": "custom",
            "name": "Engineering Team",
            "customers": [
                {
                    "object": "customer",
                    "id": "cus_1234567890abcdef",
                    "name": "John Doe",
                    "email": "john@example.com"
                }
            ],
            "created": 1640995200,
            "livemode": false
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

---

### Message Management

#### Send Message

**POST** `/messages`

Sends a message to a channel.

**Request Body:**
```json
{
    "channel_uuid": "ch_1234567890abcdef",
    "sender_uuid": "cus_1234567890abcdef",
    "type": "text",
    "content": "Hello, this is a test message!",
    "metadata": {
        "priority": "high",
        "tags": ["important", "urgent"]
    }
}
```

**Response:**
```json
{
    "object": "message",
    "id": "msg_1234567890abcdef",
    "channel_id": "ch_1234567890abcdef",
    "sender_id": "cus_1234567890abcdef",
    "type": "text",
    "content": "Hello, this is a test message!",
    "metadata": {
        "priority": "high",
        "tags": ["important", "urgent"]
    },
    "created": 1640995200,
    "livemode": false
}
```

**Supported Message Types:**
- `text` - Plain text messages
- `image` - Image messages (content should contain image URL)
- `file` - File messages (content should contain file URL)
- `system` - System-generated messages

#### Get Channel Messages

**GET** `/messages/channel/{channel_uuid}`

Retrieves messages from a specific channel, ordered by creation time (oldest first).

**Query Parameters:**
- `limit` (optional): Number of messages per page (default: 10, max: 100)
- `starting_after` (optional): Message UUID to start after for pagination

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "message",
            "id": "msg_1234567890abcdef",
            "channel_id": "ch_1234567890abcdef",
            "sender_id": "cus_1234567890abcdef",
            "type": "text",
            "content": "Hello, this is a test message!",
            "metadata": {
                "priority": "high",
                "tags": ["important", "urgent"]
            },
            "created": 1640995200,
            "livemode": false
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

#### Get Customer Messages

**GET** `/messages/customer/{customer_uuid}`

Retrieves all messages sent by a specific customer across all channels, ordered by creation time (newest first).

**Query Parameters:**
- `limit` (optional): Number of messages per page (default: 10, max: 100)
- `starting_after` (optional): Message UUID to start after for pagination

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "message",
            "id": "msg_1234567890abcdef",
            "channel_id": "ch_1234567890abcdef",
            "sender_id": "cus_1234567890abcdef",
            "type": "text",
            "content": "Hello, this is a test message!",
            "metadata": {
                "priority": "high",
                "tags": ["important", "urgent"]
            },
            "created": 1640995200,
            "livemode": false
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

---

## Response Formats

### Success Responses

#### Single Object Response
```json
{
    "object": "customer|channel|message",
    "id": "unique_uuid_identifier",
    // ... object-specific fields
    "created": 1640995200,
    "livemode": false
}
```

#### List Response
```json
{
    "object": "list",
    "data": [
        // ... array of objects
    ],
    "has_more": false,
    "total_count": 1
}
```

### Error Responses

#### Validation Error (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "name": ["The name field is required."]
    }
}
```

#### Authentication Error (401)
```json
{
    "error": "Unauthorized - Missing or invalid Authorization header"
}
```

#### Not Found Error (404)
```json
{
    "error": "Customer not found"
}
```

#### Server Error (500)
```json
{
    "error": "Failed to retrieve messages. Please try again."
}
```

---

## Error Handling

The API uses standard HTTP status codes:

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

### Common Error Scenarios

1. **Missing Authentication**: Returns 401 with specific error message
2. **Invalid Public Key**: Returns 401 with public key error
3. **Resource Not Found**: Returns 404 with resource-specific error
4. **Validation Failures**: Returns 422 with field-specific errors

---

## Pagination

The API supports cursor-based pagination for list endpoints:

### Parameters

- `limit`: Number of items per page (default: 10, max: 100)
- `starting_after`: UUID of the last item from the previous page

### Example

```http
GET /api/v1/customers?limit=5&starting_after=cus_1234567890abcdef
```

### Pagination Logic

1. **Channel Messages**: Ordered by creation time (oldest first)
2. **Customer Messages**: Ordered by creation time (newest first)
3. **Channels**: Ordered by creation time (newest first)
4. **Customers**: Ordered by creation time (newest first)

---

## Code Examples

### cURL Examples

#### Create Customer
```bash
curl -X POST https://your-api-domain.com/api/v1/customers \
  -H "Authorization: Bearer sk_test_1234567890abcdef" \
  -H "X-Public-Key: pk_test_1234567890abcdef" \
  -H "Origin: https://yourdomain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com"
  }'
```

#### Create Channel
```bash
curl -X POST https://your-api-domain.com/api/v1/channels \
  -H "Authorization: Bearer sk_test_1234567890abcdef" \
  -H "X-Public-Key: pk_test_1234567890abcdef" \
  -H "Origin: https://yourdomain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "general",
    "customer_uuids": ["cus_1234567890abcdef", "cus_0987654321fedcba"]
  }'
```

#### Send Message
```bash
curl -X POST https://your-api-domain.com/api/v1/messages \
  -H "Authorization: Bearer sk_test_1234567890abcdef" \
  -H "X-Public-Key: pk_test_1234567890abcdef" \
  -H "Origin: https://yourdomain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "channel_uuid": "ch_1234567890abcdef",
    "sender_uuid": "cus_1234567890abcdef",
    "type": "text",
    "content": "Hello, how are you?"
  }'
```

#### Get Channel Messages
```bash
curl -X GET "https://your-api-domain.com/api/v1/messages/channel/ch_1234567890abcdef?limit=10" \
  -H "Authorization: Bearer sk_test_1234567890abcdef" \
  -H "X-Public-Key: pk_test_1234567890abcdef" \
  -H "Origin: https://yourdomain.com"
```

### JavaScript Example

```javascript
// Initialize the API client
const api = new SlimeTalksAPI({
  baseURL: 'https://your-api-domain.com/api/v1',
  token: 'sk_test_1234567890abcdef',
  publicKey: 'pk_test_1234567890abcdef',
  origin: 'https://yourdomain.com'
});

// Create a customer
const customer = await api.customers.create({
  name: 'John Doe',
  email: 'john@example.com'
});

// Create a channel
const channel = await api.channels.create({
  type: 'general',
  customer_uuids: [customer.id, 'cus_0987654321fedcba']
});

// Send a message
const message = await api.messages.send({
  channel_uuid: channel.id,
  sender_uuid: customer.id,
  type: 'text',
  content: 'Hello, how are you?'
});

// Retrieve messages
const messages = await api.messages.getChannelMessages(channel.id, {
  limit: 10
});
```

### PHP Example

```php
<?php

use Illuminate\Support\Facades\Http;

$client = Http::withHeaders([
    'Authorization' => 'Bearer sk_test_1234567890abcdef',
    'X-Public-Key' => 'pk_test_1234567890abcdef',
    'Origin' => 'https://yourdomain.com',
    'Content-Type' => 'application/json'
]);

// Create a customer
$customer = $client->post('https://your-api-domain.com/api/v1/customers', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->json();

// Create a channel
$channel = $client->post('https://your-api-domain.com/api/v1/channels', [
    'type' => 'general',
    'customer_uuids' => [$customer['id'], 'cus_0987654321fedcba']
])->json();

// Send a message
$message = $client->post('https://your-api-domain.com/api/v1/messages', [
    'channel_uuid' => $channel['id'],
    'sender_uuid' => $customer['id'],
    'type' => 'text',
    'content' => 'Hello, how are you?'
])->json();
```

---

## SDK Integration

We provide SDKs for easy integration:

### JavaScript SDK
- **File**: `sdk/javascript/slime-talks-sdk.js`
- **Real-time**: `sdk/javascript/slime-talks-realtime.js`
- **Documentation**: `sdk/javascript/README.md`

### PHP SDK
- **File**: `sdk/php/SlimeTalksClient.php`
- **Documentation**: `sdk/php/README.md`

### Chat Demo
- **Demo**: `sdk/javascript/chat-demo.html`
- **Production**: `sdk/javascript/production-chat.html`
- **Setup Guide**: `sdk/javascript/CHAT_DEMO_SETUP.md`

---

## Real-time Features

The API supports real-time messaging through WebSockets:

### Events
- `message.sent` - New message sent to channel
- `typing.started` - User started typing
- `typing.stopped` - User stopped typing
- `user.joined` - User joined channel
- `user.left` - User left channel

### Channel Names
- Private: `channel.{channel_uuid}`
- Presence: `presence.channel.{channel_uuid}`

---

## Support

For technical support or questions about the API:

- **Documentation**: This file
- **Test Suite**: 116 tests with 452 assertions
- **User Stories**: `USER_STORIES.md`
- **Integration Guide**: `INTEGRATION_GUIDE.md`

---

**Built with ❤️ using Laravel and Test-Driven Development**
