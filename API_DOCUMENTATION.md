# Slime Talks Messaging API Documentation

A comprehensive messaging API built with Laravel v12, designed for multi-tenant applications. This API provides secure, scalable messaging capabilities with client isolation, authentication, and full pagination support.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Base URL](#base-url)
- [API Endpoints](#api-endpoints)
  - [Client Management](#client-management)
  - [Customer Management](#customer-management)
  - [Channel Management](#channel-management)
  - [Message Management](#message-management)
- [Response Formats](#response-formats)
- [Error Handling](#error-handling)
- [Pagination](#pagination)
- [Rate Limiting](#rate-limiting)
- [Examples](#examples)

## Overview

The Slime Talks Messaging API is a multi-tenant messaging system that allows applications to:

- **Manage Clients**: Create and authenticate client applications
- **Manage Customers**: Create and manage customer accounts
- **Manage Channels**: Create general (direct) and custom (topic-specific) channels
- **Send Messages**: Send messages between customers in channels
- **Retrieve Messages**: Get messages by channel or customer with full pagination

### Key Features

- ✅ **Multi-tenant Architecture**: Complete client isolation
- ✅ **Secure Authentication**: Bearer tokens + public key validation
- ✅ **Domain Validation**: Origin header checking
- ✅ **Comprehensive Pagination**: Cursor-based pagination support
- ✅ **Stripe-inspired API**: Consistent JSON responses
- ✅ **Full Test Coverage**: 107 tests with 414 assertions
- ✅ **Production Ready**: Error logging and validation

## Authentication

All API endpoints require authentication using a combination of:

1. **Authorization Header**: `Bearer {token}`
2. **X-Public-Key Header**: Client's public key
3. **Origin Header**: Must match client's registered domain

### Example Headers

```http
Authorization: Bearer sk_test_1234567890abcdef
X-Public-Key: pk_test_1234567890abcdef
Origin: https://yourdomain.com
```

## Base URL

```
https://your-api-domain.com/api/v1
```

## API Endpoints

### Client Management

#### Get Client Information

**GET** `/client/{client_uuid}`

Retrieves information about a specific client.

**Headers:**
- `Authorization: Bearer {token}`
- `X-Public-Key: {public_key}`
- `Origin: {domain}`

**Response:**
```json
{
    "object": "client",
    "id": "clt_1234567890",
    "name": "Example Client",
    "domain": "example.com",
    "public_key": "pk_test_1234567890",
    "allowed_ips": ["127.0.0.1"],
    "allowed_subdomains": ["api", "app"],
    "created": 1640995200,
    "livemode": false
}
```

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
    "id": "cus_1234567890",
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
    "id": "cus_1234567890",
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

Lists all customers for the authenticated client.

**Query Parameters:**
- `limit` (optional): Number of customers per page (default: 10)
- `starting_after` (optional): Customer UUID to start after

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "customer",
            "id": "cus_1234567890",
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

### Channel Management

#### Create General Channel

**POST** `/channels`

Creates a general (direct message) channel between customers.

**Request Body:**
```json
{
    "type": "general",
    "customer_uuids": ["cus_1234567890", "cus_0987654321"]
}
```

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890",
    "type": "general",
    "name": "general",
    "customers": [
        {
            "object": "customer",
            "id": "cus_1234567890",
            "name": "John Doe",
            "email": "john@example.com"
        },
        {
            "object": "customer",
            "id": "cus_0987654321",
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

Creates a custom (topic-specific) channel.

**Request Body:**
```json
{
    "type": "custom",
    "name": "Engineering Team",
    "customer_uuids": ["cus_1234567890", "cus_0987654321", "cus_1122334455"]
}
```

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890",
    "type": "custom",
    "name": "Engineering Team",
    "customers": [
        {
            "object": "customer",
            "id": "cus_1234567890",
            "name": "John Doe",
            "email": "john@example.com"
        },
        {
            "object": "customer",
            "id": "cus_0987654321",
            "name": "Jane Smith",
            "email": "jane@example.com"
        },
        {
            "object": "customer",
            "id": "cus_1122334455",
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
    "id": "ch_1234567890",
    "type": "custom",
    "name": "Engineering Team",
    "customers": [
        {
            "object": "customer",
            "id": "cus_1234567890",
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
- `limit` (optional): Number of channels per page (default: 10)
- `starting_after` (optional): Channel UUID to start after

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_1234567890",
            "type": "custom",
            "name": "Engineering Team",
            "customers": [
                {
                    "object": "customer",
                    "id": "cus_1234567890",
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

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_1234567890",
            "type": "custom",
            "name": "Engineering Team",
            "customers": [
                {
                    "object": "customer",
                    "id": "cus_1234567890",
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

### Message Management

#### Send Message

**POST** `/messages`

Sends a message to a channel.

**Request Body:**
```json
{
    "channel_uuid": "ch_1234567890",
    "sender_uuid": "cus_1234567890",
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
    "id": "msg_1234567890",
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

#### Get Channel Messages

**GET** `/messages/channel/{channel_uuid}`

Retrieves messages from a specific channel, ordered by creation time (oldest first).

**Query Parameters:**
- `limit` (optional): Number of messages per page (default: 10)
- `starting_after` (optional): Message UUID to start after

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "message",
            "id": "msg_1234567890",
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
- `limit` (optional): Number of messages per page (default: 10)
- `starting_after` (optional): Message UUID to start after

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "message",
            "id": "msg_1234567890",
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

## Response Formats

### Success Responses

All successful responses follow a consistent format:

#### Single Object Response
```json
{
    "object": "customer|channel|message",
    "id": "unique_identifier",
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

## Error Handling

The API uses standard HTTP status codes:

- **200**: Success
- **201**: Created
- **400**: Bad Request
- **401**: Unauthorized
- **403**: Forbidden
- **404**: Not Found
- **422**: Validation Error
- **500**: Server Error

### Common Error Scenarios

1. **Missing Authentication**: Returns 401 with specific error message
2. **Invalid Public Key**: Returns 401 with public key error
3. **Invalid Origin**: Returns 401 with domain validation error
4. **Resource Not Found**: Returns 404 with resource-specific error
5. **Validation Failures**: Returns 422 with field-specific errors

## Pagination

The API supports cursor-based pagination for list endpoints:

### Parameters

- `limit`: Number of items per page (default: 10, max: 100)
- `starting_after`: UUID of the last item from the previous page

### Example

```http
GET /api/v1/customers?limit=5&starting_after=cus_1234567890
```

### Response Format

```json
{
    "object": "list",
    "data": [...],
    "has_more": true,
    "total_count": 25
}
```

### Pagination Logic

1. **Channel Messages**: Ordered by creation time (oldest first)
2. **Customer Messages**: Ordered by creation time (newest first)
3. **Channels**: Ordered by creation time (newest first)
4. **Customers**: Ordered by creation time (newest first)

## Rate Limiting

Currently, the API does not implement rate limiting. Consider implementing rate limiting based on your infrastructure needs:

- **Per Client**: Limit requests per client per minute
- **Per Endpoint**: Different limits for different endpoints
- **Burst Protection**: Allow short bursts with sustained limits

## Examples

### Complete Workflow Example

#### 1. Create Customers

```bash
curl -X POST https://your-api-domain.com/api/v1/customers \
  -H "Authorization: Bearer sk_test_1234567890" \
  -H "X-Public-Key: pk_test_1234567890" \
  -H "Origin: https://yourdomain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com"
  }'
```

#### 2. Create Channel

```bash
curl -X POST https://your-api-domain.com/api/v1/channels \
  -H "Authorization: Bearer sk_test_1234567890" \
  -H "X-Public-Key: pk_test_1234567890" \
  -H "Origin: https://yourdomain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "general",
    "customer_uuids": ["cus_1234567890", "cus_0987654321"]
  }'
```

#### 3. Send Message

```bash
curl -X POST https://your-api-domain.com/api/v1/messages \
  -H "Authorization: Bearer sk_test_1234567890" \
  -H "X-Public-Key: pk_test_1234567890" \
  -H "Origin: https://yourdomain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "channel_uuid": "ch_1234567890",
    "sender_uuid": "cus_1234567890",
    "type": "text",
    "content": "Hello, how are you?"
  }'
```

#### 4. Retrieve Messages

```bash
curl -X GET "https://your-api-domain.com/api/v1/messages/channel/ch_1234567890?limit=10" \
  -H "Authorization: Bearer sk_test_1234567890" \
  -H "X-Public-Key: pk_test_1234567890" \
  -H "Origin: https://yourdomain.com"
```

### JavaScript SDK Example

```javascript
// Initialize the API client
const api = new SlimeTalksAPI({
  baseURL: 'https://your-api-domain.com/api/v1',
  token: 'sk_test_1234567890',
  publicKey: 'pk_test_1234567890',
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
  customer_uuids: [customer.id, 'cus_0987654321']
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

## Testing

The API includes comprehensive test coverage:

- **107 tests** with **414 assertions**
- **Unit tests** for individual components
- **Feature tests** for API endpoints
- **Integration tests** for complete workflows

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter="MessageTest"

# Run with coverage
php artisan test --coverage
```

## Support

For technical support or questions about the API:

- **Documentation**: This file
- **Test Suite**: `tests/Feature/` directory
- **User Stories**: `USER_STORIES.md`
- **Code Examples**: Test files contain comprehensive examples

---

**Built with ❤️ using Laravel v12 and Test-Driven Development**
