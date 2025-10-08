# Slime Talks - Messaging API

A comprehensive messaging API that provides chat functionality for any application. Built with Laravel and designed to be integrated into existing applications as a messaging service.

## Overview

Slime Talks is a client-based messaging API that allows applications to implement chat functionality without building their own messaging infrastructure. Each client can have multiple customers, channels, and messages, all properly isolated and secured.

## Architecture

### Core Concepts

- **Client**: The main entity that represents an application using the messaging service
- **Customer**: Users within a client's application who can send and receive messages
- **Channel**: Communication channels between customers (General or Custom)
- **Message**: Individual messages sent within channels

### Data Model

```
Client (1) ──→ (N) Customer
Client (1) ──→ (N) Channel
Client (1) ──→ (N) Message
Customer (N) ──→ (N) Channel (Many-to-Many)
Channel (1) ──→ (N) Message
```

## Features

### Client Management
- **Secure Authentication**: Each client has a unique public key and API token
- **Domain Validation**: Requests are validated against registered domains
- **Artisan Command**: Easy client creation via `php artisan slime-chat:start-client`

### Channel Types
- **General Channel**: Default direct messaging between two customers
- **Custom Channel**: Topic-specific conversations between customers
- **Auto-Creation**: General channels are automatically created when custom channels are established

### Security
- **Client-Based Authentication**: All endpoints require valid client credentials
- **Origin Validation**: Requests must come from registered domains
- **Token-Based Security**: API tokens are bound to specific clients
- **UUID-Based IDs**: All public-facing IDs use UUIDs for security

## API Endpoints

### Authentication
All endpoints require the following headers:
- `Authorization: Bearer {token}`
- `X-Public-Key: {public_key}`
- `Origin: {domain}` (for browser requests)

### Client Endpoints
- `GET /api/v1/client/{uuid}` - Get client information

### Customer Endpoints
- `POST /api/v1/customers` - Create a new customer
- `GET /api/v1/customers/{uuid}` - Get customer information
- `GET /api/v1/customers` - List all customers for a client

### Channel Endpoints
- `POST /api/v1/channels` - Create a new channel
- `GET /api/v1/channels/{uuid}` - Get channel information
- `GET /api/v1/channels` - List all channels for a client
- `GET /api/v1/channels/customer/{customer_uuid}` - Get channels for a specific customer

### Message Endpoints
- `POST /api/v1/messages` - Send a new message
- `GET /api/v1/messages/channel/{channel_uuid}` - Get messages for a channel
- `GET /api/v1/messages/customer/{customer_uuid}` - Get messages for a customer

## Installation & Setup

### Prerequisites
- PHP 8.1+
- Laravel 11+
- MySQL/PostgreSQL
- Composer

### Installation
```bash
# Clone the repository
git clone <repository-url>
cd slime-talks

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Create a client
php artisan slime-chat:start-client
```

### Environment Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=slime_talks
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

## Usage Examples

### Creating a Client
```bash
php artisan slime-chat:start-client
```

This will prompt for:
- Client name
- Domain where requests will be received

The command returns:
- Client UUID
- Public Key (for X-Public-Key header)
- API Token (for Authorization header)

### API Usage

#### Create a Customer
```bash
curl -X POST https://your-api.com/api/v1/customers \
  -H "Authorization: Bearer your-token" \
  -H "X-Public-Key: your-public-key" \
  -H "Origin: your-domain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com"
  }'
```

#### Create a Channel
```bash
curl -X POST https://your-api.com/api/v1/channels \
  -H "Authorization: Bearer your-token" \
  -H "X-Public-Key: your-public-key" \
  -H "Origin: your-domain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "general",
    "customer1_uuid": "customer-uuid-1",
    "customer2_uuid": "customer-uuid-2"
  }'
```

#### Send a Message
```bash
curl -X POST https://your-api.com/api/v1/messages \
  -H "Authorization: Bearer your-token" \
  -H "X-Public-Key: your-public-key" \
  -H "Origin: your-domain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "channel_uuid": "channel-uuid",
    "sender_uuid": "customer-uuid",
    "content": "Hello, how are you?",
    "message_type": "text"
  }'
```

## Data Models

### Client
- `id`: Primary key
- `uuid`: Public-facing identifier
- `name`: Client name
- `domain`: Registered domain
- `public_key`: Authentication key
- `allowed_ips`: IP restrictions (optional)
- `allowed_subdomains`: Subdomain restrictions (optional)

### Customer
- `id`: Primary key
- `uuid`: Public-facing identifier
- `client_id`: Associated client
- `name`: Customer name
- `email`: Customer email
- `metadata`: Additional customer data (JSON)

### Channel
- `id`: Primary key
- `uuid`: Public-facing identifier
- `client_id`: Associated client
- `type`: "general" or "custom"
- `name`: Channel name (for custom channels)
- `created_at`: Channel creation time

### Message
- `id`: Primary key
- `uuid`: Public-facing identifier
- `client_id`: Associated client
- `channel_id`: Associated channel
- `sender_id`: Customer who sent the message
- `content`: Message content
- `message_type`: Type of message (text, image, file, etc.)
- `metadata`: Additional message data (JSON)
- `created_at`: Message timestamp

## Security

### Authentication Flow
1. Client provides API token in Authorization header
2. Client provides public key in X-Public-Key header
3. System validates token belongs to client with matching public key
4. System validates request origin against client's registered domain
5. Request is authorized if all checks pass

### Best Practices
- Store API tokens securely
- Use HTTPS in production
- Implement rate limiting
- Monitor for suspicious activity
- Regularly rotate API tokens

## Development

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=ClientTest

# Run with coverage
php artisan test --coverage
```

### Code Standards
- Follow PSR-12 coding standards
- Use strict typing
- Write comprehensive PHPDoc blocks
- Implement Test-Driven Development (TDD)
- Use API Resources for responses
- Create Form Request classes for validation

### Contributing
1. Write tests first (TDD approach)
2. Follow coding standards
3. Ensure all tests pass
4. Update documentation
5. Submit pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please contact the development team or create an issue in the repository.