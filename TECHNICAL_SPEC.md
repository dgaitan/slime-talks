# Technical Specification - Slime Talks Messaging API

## System Architecture

### High-Level Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Client App 1  │    │   Client App 2  │    │   Client App N  │
│                 │    │                 │    │                 │
│  ┌───────────┐  │    │  ┌───────────┐  │    │  ┌───────────┐  │
│  │ Customers │  │    │  │ Customers │  │    │  │ Customers │  │
│  │ Channels  │  │    │  │ Channels  │  │    │  │ Channels  │  │
│  │ Messages  │  │    │  │ Messages  │  │    │  │ Messages  │  │
│  └───────────┘  │    │  └───────────┘  │    │  └───────────┘  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │  Slime Talks    │
                    │  Messaging API  │
                    │                 │
                    │  ┌───────────┐  │
                    │  │  Clients  │  │
                    │  │ Customers │  │
                    │  │ Channels  │  │
                    │  │ Messages  │  │
                    │  └───────────┘  │
                    └─────────────────┘
```

### Database Schema

#### Clients Table
```sql
CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    public_key TEXT NOT NULL,
    allowed_ips JSON NULL,
    allowed_subdomains JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

#### Customers Table
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_per_client (client_id, email)
);
```

#### Channels Table
```sql
CREATE TABLE channels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    client_id BIGINT UNSIGNED NOT NULL,
    type ENUM('general', 'custom') NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

#### Channel-Customer Pivot Table
```sql
CREATE TABLE channel_customer (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_channel_customer (channel_id, customer_id)
);
```

#### Messages Table
```sql
CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    client_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    message_type ENUM('text', 'image', 'file', 'system') DEFAULT 'text',
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

## API Specifications

### Authentication

All API requests must include:
- `Authorization: Bearer {token}` - API token from client
- `X-Public-Key: {public_key}` - Client's public key
- `Origin: {domain}` - Request origin (for browser requests)

### Response Format

#### Success Response
```json
{
    "object": "customer",
    "id": "cust_1234567890",
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {},
    "created": 1640995200,
    "livemode": false
}
```

#### Error Response
```json
{
    "error": {
        "type": "invalid_request_error",
        "code": "parameter_missing",
        "message": "Missing required parameter: name"
    }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 201  | Created |
| 400  | Bad Request |
| 401  | Unauthorized |
| 403  | Forbidden |
| 404  | Not Found |
| 422  | Validation Error |
| 500  | Server Error |

### Endpoints Specification

#### Customer Endpoints

##### Create Customer
```
POST /api/v1/customers
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {
        "avatar": "https://example.com/avatar.jpg",
        "preferences": {
            "notifications": true
        }
    }
}
```

**Response:**
```json
{
    "object": "customer",
    "id": "cust_1234567890",
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {
        "avatar": "https://example.com/avatar.jpg",
        "preferences": {
            "notifications": true
        }
    },
    "created": 1640995200,
    "livemode": false
}
```

##### Get Customer
```
GET /api/v1/customers/{uuid}
```

**Response:**
```json
{
    "object": "customer",
    "id": "cust_1234567890",
    "name": "John Doe",
    "email": "john@example.com",
    "metadata": {},
    "created": 1640995200,
    "livemode": false
}
```

##### List Customers
```
GET /api/v1/customers?limit=10&starting_after=cust_1234567890
```

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "customer",
            "id": "cust_1234567890",
            "name": "John Doe",
            "email": "john@example.com",
            "created": 1640995200
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

#### Channel Endpoints

##### Create Channel
```
POST /api/v1/channels
```

**Request Body (General Channel):**
```json
{
    "type": "general",
    "customer1_uuid": "cust_1234567890",
    "customer2_uuid": "cust_0987654321"
}
```

**Request Body (Custom Channel):**
```json
{
    "type": "custom",
    "name": "Project Discussion",
    "customer1_uuid": "cust_1234567890",
    "customer2_uuid": "cust_0987654321"
}
```

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890",
    "type": "general",
    "name": "general",
    "participants": [
        {
            "object": "customer",
            "id": "cust_1234567890",
            "name": "John Doe"
        },
        {
            "object": "customer",
            "id": "cust_0987654321",
            "name": "Jane Smith"
        }
    ],
    "created": 1640995200,
    "livemode": false
}
```

##### Get Channel
```
GET /api/v1/channels/{uuid}
```

**Response:**
```json
{
    "object": "channel",
    "id": "ch_1234567890",
    "type": "general",
    "name": "general",
    "participants": [
        {
            "object": "customer",
            "id": "cust_1234567890",
            "name": "John Doe"
        }
    ],
    "created": 1640995200,
    "livemode": false
}
```

##### List Channels
```
GET /api/v1/channels?limit=10&starting_after=ch_1234567890
```

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_1234567890",
            "type": "general",
            "name": "general",
            "created": 1640995200
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

##### Get Customer Channels
```
GET /api/v1/channels/customer/{customer_uuid}?limit=10&starting_after=ch_1234567890
```

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_1234567890",
            "type": "general",
            "name": "general",
            "created": 1640995200
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

#### Message Endpoints

##### Send Message
```
POST /api/v1/messages
```

**Request Body:**
```json
{
    "channel_uuid": "ch_1234567890",
    "sender_uuid": "cust_1234567890",
    "content": "Hello, how are you?",
    "message_type": "text",
    "metadata": {
        "attachments": [],
        "reply_to": null
    }
}
```

**Response:**
```json
{
    "object": "message",
    "id": "msg_1234567890",
    "channel_id": "ch_1234567890",
    "sender": {
        "object": "customer",
        "id": "cust_1234567890",
        "name": "John Doe"
    },
    "content": "Hello, how are you?",
    "message_type": "text",
    "metadata": {
        "attachments": [],
        "reply_to": null
    },
    "created": 1640995200,
    "livemode": false
}
```

##### Get Channel Messages
```
GET /api/v1/messages/channel/{channel_uuid}?limit=20&starting_after=msg_1234567890
```

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "message",
            "id": "msg_1234567890",
            "channel_id": "ch_1234567890",
            "sender": {
                "object": "customer",
                "id": "cust_1234567890",
                "name": "John Doe"
            },
            "content": "Hello, how are you?",
            "message_type": "text",
            "created": 1640995200
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

##### Get Customer Messages
```
GET /api/v1/messages/customer/{customer_uuid}?limit=20&starting_after=msg_1234567890
```

**Response:**
```json
{
    "object": "list",
    "data": [
        {
            "object": "message",
            "id": "msg_1234567890",
            "channel_id": "ch_1234567890",
            "sender": {
                "object": "customer",
                "id": "cust_1234567890",
                "name": "John Doe"
            },
            "content": "Hello, how are you?",
            "message_type": "text",
            "created": 1640995200
        }
    ],
    "has_more": false,
    "total_count": 1
}
```

## Security Considerations

### Authentication Flow
1. Client provides API token in Authorization header
2. Client provides public key in X-Public-Key header
3. System validates token belongs to client with matching public key
4. System validates request origin against client's registered domain
5. Request is authorized if all checks pass

### Data Isolation
- All data is isolated by client
- Customers can only see their own client's data
- Channels are restricted to client's customers
- Messages are restricted to client's channels

### Rate Limiting
- Implement rate limiting per client
- Different limits for different endpoints
- Monitor for abuse patterns

### Data Protection
- All public-facing IDs use UUIDs
- Sensitive data is never exposed in API responses
- Database IDs are never exposed
- Soft deletes for data retention

## Performance Considerations

### Database Indexing
- Index on UUID columns for fast lookups
- Index on client_id for data isolation
- Index on created_at for time-based queries
- Composite indexes for common query patterns

### Caching Strategy
- Cache client authentication data
- Cache frequently accessed channel data
- Implement Redis for session storage

### Pagination
- All list endpoints support pagination
- Use cursor-based pagination for consistency
- Limit maximum page size to prevent abuse

## Monitoring & Logging

### Metrics to Track
- API request volume per client
- Response times per endpoint
- Error rates by endpoint
- Authentication failures
- Rate limit violations

### Logging
- Log all API requests with client identification
- Log authentication failures
- Log rate limit violations
- Log system errors

### Alerts
- High error rate alerts
- Authentication failure spikes
- Rate limit violation spikes
- System performance degradation
