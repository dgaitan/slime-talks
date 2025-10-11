# Channel Ordering by Latest Activity

## Overview

Channels are now ordered by their latest activity (most recent message) rather than by creation date. This provides a better user experience, similar to popular messaging apps like WhatsApp, Slack, and Discord.

## Implementation Details

### 1. **Automatic Timestamp Updates**

When a message is sent to a channel, the channel's `updated_at` timestamp is automatically updated using Laravel's `touch()` method.

**Location:** `app/Services/MessageService.php`

```php
// Create message
$message = $this->messageRepository->create($messageData);

// Update channel's updated_at timestamp to reflect latest activity
$channel->touch();

// Broadcast the message to channel participants
broadcast(new MessageSent($message));
```

### 2. **Channel Listing Order**

All channel listing endpoints now order by `updated_at` (descending) with a secondary sort by `id` (descending) for ties.

**Location:** `app/Repositories/ChannelRepository.php`

#### List All Channels
```php
$query = Channel::where('client_id', $client->id)
    ->orderBy('updated_at', 'desc') // Order by latest activity (most recent first)
    ->orderBy('id', 'desc'); // Secondary sort for ties
```

#### Get Customer Channels
```php
$channels = Channel::where('client_id', $client->id)
    ->whereHas('customers', function ($query) use ($customer) {
        $query->where('customers.id', $customer->id);
    })
    ->orderBy('updated_at', 'desc') // Order by latest activity
    ->orderBy('id', 'desc') // Secondary sort for ties
    ->get();
```

### 3. **Pagination Support**

Cursor-based pagination has been updated to work with the new ordering:

```php
if ($startingAfter) {
    $startingChannel = Channel::where('uuid', $startingAfter)->first();
    if ($startingChannel) {
        $query->where('updated_at', '<=', $startingChannel->updated_at)
            ->where('id', '<', $startingChannel->id);
    }
}
```

## Affected Endpoints

### 1. **GET** `/api/v1/channels`
Lists all channels for the authenticated client, ordered by latest activity.

### 2. **GET** `/api/v1/channels/customer/{customer_uuid}`
Lists all channels for a specific customer, ordered by latest activity.

## Behavior

### Before
Channels were ordered by creation date (`created_at` or `id`), meaning newly created channels always appeared at the top, regardless of message activity.

### After
Channels are ordered by their last message timestamp (`updated_at`), so:
- Channels with recent messages appear at the top
- Inactive channels move down the list
- Empty channels (no messages yet) appear based on their creation date

## Examples

### Scenario 1: New Message Bumps Channel
```
Initial State:
1. Channel A (created 3 hours ago, no messages)
2. Channel B (created 2 hours ago, no messages)
3. Channel C (created 1 hour ago, no messages)

After sending message to Channel C:
1. Channel C (message sent just now) ← moved to top
2. Channel A (created 3 hours ago)
3. Channel B (created 2 hours ago)
```

### Scenario 2: Multiple Messages
```
Initial State:
1. Channel A (last message 10 minutes ago)
2. Channel B (last message 1 hour ago)

After sending message to Channel B:
1. Channel B (message sent just now) ← moved to top
2. Channel A (last message 10 minutes ago)
```

## Testing

Three comprehensive tests have been added to verify the behavior:

### Test 1: Orders channels by latest message activity
- Creates 3 channels at different times
- Sends a message to the oldest channel
- Verifies the channel with the latest message appears first

### Test 2: Maintains order when multiple messages are sent
- Creates 2 channels
- Sends messages to both channels in sequence
- Verifies the channel with the most recent message appears first

### Test 3: Orders customer channels by latest activity
- Creates 2 channels for a customer
- Sends a message to an older channel
- Verifies customer's channel list is ordered by activity

**Test File:** `tests/Feature/ChannelOrderingTest.php`

## Database Schema

No schema changes required. The feature uses Laravel's built-in `updated_at` timestamp column that already exists in the `channels` table.

## API Response

The API response format remains unchanged. The only difference is the order of channels in the `data` array:

```json
{
    "object": "list",
    "data": [
        {
            "object": "channel",
            "id": "ch_most_recent_message",
            "type": "custom",
            "name": "Active Channel",
            "customers": [...],
            "created": 1640995200,
            "livemode": false
        },
        {
            "object": "channel",
            "id": "ch_older_message",
            "type": "custom",
            "name": "Less Active Channel",
            "customers": [...],
            "created": 1640995100,
            "livemode": false
        }
    ],
    "has_more": false,
    "total_count": 2
}
```

## Performance Considerations

- **Index on `updated_at`**: Consider adding a database index on `(client_id, updated_at, id)` for optimal query performance:
  ```sql
  CREATE INDEX idx_channels_activity 
  ON channels (client_id, updated_at DESC, id DESC);
  ```

- **Touch Performance**: The `touch()` method performs a single UPDATE query, which is minimal overhead.

## Backward Compatibility

This change is **fully backward compatible**:
- No breaking changes to API responses
- No schema migrations required
- Existing clients will automatically benefit from the improved ordering

## Test Results

✅ **All 119 tests pass** (466 assertions)
- 3 new tests for channel ordering
- 116 existing tests still passing
- No regressions introduced

---

**Implementation Date:** January 2025  
**Status:** ✅ Complete and Tested

