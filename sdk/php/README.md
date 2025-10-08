# Slime Talks PHP SDK

A comprehensive PHP SDK for integrating with the Slime Talks Messaging API. This SDK provides a clean, object-oriented interface for all API operations.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or higher (includes HTTP client)
- Composer

## Installation

This SDK uses Laravel's built-in HTTP client, so no additional packages are required!

Copy the `SlimeTalksClient.php` file to your project:

```bash
# Option 1: Copy to your project's src directory
cp SlimeTalksClient.php /path/to/your/project/src/SlimeTalks/

# Option 2: Copy to your Laravel app directory
cp SlimeTalksClient.php /path/to/laravel/app/Services/SlimeTalks/
```

### Autoloading

If you're using Laravel, the class will be autoloaded automatically if placed in the `app/` directory.

For non-Laravel projects, add to your `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "SlimeTalks\\SDK\\": "src/SlimeTalks/"
        }
    }
}
```

Then run:

```bash
composer dump-autoload
```

## Configuration

### Laravel Setup

Create a configuration file `config/slimetalks.php`:

```php
<?php

return [
    'base_url' => env('SLIME_TALKS_API_URL', 'https://api.slime-talks.com/api/v1'),
    'secret_key' => env('SLIME_TALKS_SECRET_KEY'),
    'public_key' => env('SLIME_TALKS_PUBLIC_KEY'),
    'origin' => env('SLIME_TALKS_ORIGIN', 'https://yourdomain.com'),
    'timeout' => 30,
    'verify' => env('APP_ENV') === 'production',
];
```

Add to your `.env` file:

```env
SLIME_TALKS_API_URL=https://api.slime-talks.com/api/v1
SLIME_TALKS_SECRET_KEY=sk_live_your_secret_key_here
SLIME_TALKS_PUBLIC_KEY=pk_live_your_public_key_here
SLIME_TALKS_ORIGIN=https://yourdomain.com
```

### Advanced Configuration (Optional)

You can also configure retry logic and other HTTP client options:

```php
<?php

return [
    'base_url' => env('SLIME_TALKS_API_URL'),
    'secret_key' => env('SLIME_TALKS_SECRET_KEY'),
    'public_key' => env('SLIME_TALKS_PUBLIC_KEY'),
    'origin' => env('SLIME_TALKS_ORIGIN'),
    'timeout' => 30,
    'retry' => [
        'times' => 3,  // Retry failed requests 3 times
        'sleep' => 100, // Wait 100ms between retries
    ],
];
```

### Service Provider Registration

Create a service provider `app/Providers/SlimeTalksServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SlimeTalks\SDK\SlimeTalksClient;

class SlimeTalksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SlimeTalksClient::class, function ($app) {
            return new SlimeTalksClient([
                'base_url' => config('slimetalks.base_url'),
                'secret_key' => config('slimetalks.secret_key'),
                'public_key' => config('slimetalks.public_key'),
                'origin' => config('slimetalks.origin'),
                'timeout' => config('slimetalks.timeout'),
                'verify' => config('slimetalks.verify'),
            ]);
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\SlimeTalksServiceProvider::class,
],
```

## Usage

### Basic Setup

```php
use SlimeTalks\SDK\SlimeTalksClient;
use SlimeTalks\SDK\SlimeTalksException;

$client = new SlimeTalksClient([
    'base_url' => 'https://api.slime-talks.com/api/v1',
    'secret_key' => 'sk_live_your_secret_key',
    'public_key' => 'pk_live_your_public_key',
    'origin' => 'https://yourdomain.com',
]);
```

### Laravel Dependency Injection

```php
use SlimeTalks\SDK\SlimeTalksClient;

class ChatController extends Controller
{
    public function __construct(
        private SlimeTalksClient $slimeTalks
    ) {}

    public function sendMessage(Request $request)
    {
        try {
            $message = $this->slimeTalks->sendMessage([
                'channel_uuid' => $request->channel_uuid,
                'sender_uuid' => $request->sender_uuid,
                'type' => 'text',
                'content' => $request->content,
            ]);

            return response()->json($message);
        } catch (SlimeTalksException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

## API Methods

### Client Management

```php
// Get client information
$client = $slimeTalks->getClient('clt_1234567890');
```

### Customer Management

```php
// Create a customer
$customer = $slimeTalks->createCustomer([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'metadata' => [
        'department' => 'Engineering',
        'role' => 'Developer'
    ]
]);

// Get a customer
$customer = $slimeTalks->getCustomer('cus_1234567890');

// List customers with pagination
$customers = $slimeTalks->listCustomers([
    'limit' => 20,
    'starting_after' => 'cus_1234567890'
]);

// Access customer data
echo $customers['data'][0]['name'];
echo $customers['has_more'];
echo $customers['total_count'];
```

### Channel Management

```php
// Create a general channel
$channel = $slimeTalks->createChannel([
    'type' => 'general',
    'customer_uuids' => ['cus_1234567890', 'cus_0987654321']
]);

// Create a custom channel
$channel = $slimeTalks->createChannel([
    'type' => 'custom',
    'name' => 'Engineering Team',
    'customer_uuids' => ['cus_1234567890', 'cus_0987654321', 'cus_1122334455']
]);

// Get a channel
$channel = $slimeTalks->getChannel('ch_1234567890');

// List channels
$channels = $slimeTalks->listChannels([
    'limit' => 10
]);

// Get channels for a customer
$channels = $slimeTalks->getCustomerChannels('cus_1234567890');
```

### Message Management

```php
// Send a text message
$message = $slimeTalks->sendMessage([
    'channel_uuid' => 'ch_1234567890',
    'sender_uuid' => 'cus_1234567890',
    'type' => 'text',
    'content' => 'Hello, this is a test message!',
    'metadata' => [
        'priority' => 'high',
        'tags' => ['important', 'urgent']
    ]
]);

// Get messages from a channel
$messages = $slimeTalks->getChannelMessages('ch_1234567890', [
    'limit' => 50,
    'starting_after' => 'msg_1234567890'
]);

// Get messages for a customer
$messages = $slimeTalks->getCustomerMessages('cus_1234567890', [
    'limit' => 50
]);

// Iterate through messages
foreach ($messages['data'] as $message) {
    echo $message['content'];
    echo $message['created'];
}
```

## Error Handling

```php
use SlimeTalks\SDK\SlimeTalksException;

try {
    $customer = $slimeTalks->createCustomer([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
} catch (SlimeTalksException $e) {
    // Handle API errors
    echo "Error: " . $e->getMessage();
    echo "Code: " . $e->getCode();
    
    // Log the error
    Log::error('Slime Talks API Error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
```

## Complete Example

```php
<?php

use SlimeTalks\SDK\SlimeTalksClient;
use SlimeTalks\SDK\SlimeTalksException;

class ChatService
{
    private SlimeTalksClient $client;

    public function __construct()
    {
        $this->client = new SlimeTalksClient([
            'base_url' => env('SLIME_TALKS_API_URL'),
            'secret_key' => env('SLIME_TALKS_SECRET_KEY'),
            'public_key' => env('SLIME_TALKS_PUBLIC_KEY'),
            'origin' => env('SLIME_TALKS_ORIGIN'),
        ]);
    }

    public function startConversation(string $user1Email, string $user2Email): array
    {
        try {
            // Create customers
            $customer1 = $this->client->createCustomer([
                'name' => $user1Email,
                'email' => $user1Email
            ]);

            $customer2 = $this->client->createCustomer([
                'name' => $user2Email,
                'email' => $user2Email
            ]);

            // Create channel
            $channel = $this->client->createChannel([
                'type' => 'general',
                'customer_uuids' => [$customer1['id'], $customer2['id']]
            ]);

            // Send welcome message
            $message = $this->client->sendMessage([
                'channel_uuid' => $channel['id'],
                'sender_uuid' => $customer1['id'],
                'type' => 'text',
                'content' => 'Conversation started!'
            ]);

            return [
                'channel' => $channel,
                'customers' => [$customer1, $customer2],
                'message' => $message
            ];
        } catch (SlimeTalksException $e) {
            throw new \Exception("Failed to start conversation: " . $e->getMessage());
        }
    }

    public function getConversationHistory(string $channelUuid): array
    {
        try {
            $allMessages = [];
            $hasMore = true;
            $startingAfter = null;

            while ($hasMore) {
                $response = $this->client->getChannelMessages($channelUuid, [
                    'limit' => 100,
                    'starting_after' => $startingAfter
                ]);

                $allMessages = array_merge($allMessages, $response['data']);
                $hasMore = $response['has_more'];
                
                if ($hasMore && count($response['data']) > 0) {
                    $startingAfter = end($response['data'])['id'];
                }
            }

            return $allMessages;
        } catch (SlimeTalksException $e) {
            throw new \Exception("Failed to get conversation history: " . $e->getMessage());
        }
    }
}

// Usage
$chatService = new ChatService();

// Start a conversation
$conversation = $chatService->startConversation(
    'user1@example.com',
    'user2@example.com'
);

// Get conversation history
$history = $chatService->getConversationHistory($conversation['channel']['id']);
```

## Testing

```php
use SlimeTalks\SDK\SlimeTalksClient;
use SlimeTalks\SDK\SlimeTalksException;

class SlimeTalksTest extends TestCase
{
    private SlimeTalksClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new SlimeTalksClient([
            'base_url' => env('SLIME_TALKS_TEST_URL'),
            'secret_key' => env('SLIME_TALKS_TEST_SECRET'),
            'public_key' => env('SLIME_TALKS_TEST_PUBLIC_KEY'),
            'origin' => 'https://test.com',
        ]);
    }

    public function test_can_create_customer(): void
    {
        $customer = $this->client->createCustomer([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertArrayHasKey('id', $customer);
        $this->assertEquals('Test User', $customer['name']);
    }

    public function test_handles_errors_gracefully(): void
    {
        $this->expectException(SlimeTalksException::class);

        $this->client->getCustomer('invalid_uuid');
    }
}
```

## Best Practices

1. **Use Dependency Injection**
   ```php
   // Good
   public function __construct(private SlimeTalksClient $slimeTalks) {}
   
   // Avoid
   $slimeTalks = new SlimeTalksClient([...]);
   ```

2. **Always Handle Exceptions**
   ```php
   try {
       $customer = $slimeTalks->createCustomer($data);
   } catch (SlimeTalksException $e) {
       Log::error('Customer creation failed', ['error' => $e->getMessage()]);
       throw $e;
   }
   ```

3. **Use Pagination for Large Lists**
   ```php
   $customers = $slimeTalks->listCustomers(['limit' => 100]);
   ```

4. **Store Configuration in Environment**
   ```php
   // Never hardcode credentials
   'secret_key' => env('SLIME_TALKS_SECRET_KEY')
   ```

## Support

For issues or questions:
- Check the [API Documentation](../../API_DOCUMENTATION.md)
- Review the [Integration Guide](../../INTEGRATION_GUIDE.md)
- Contact support: support@slime-talks.com

## License

MIT License
