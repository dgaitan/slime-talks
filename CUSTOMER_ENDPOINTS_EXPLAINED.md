# Customer-Centric Endpoints Explained

This document explains the differences between the three customer-centric endpoints and when to use each one.

## 📊 Overview of Endpoints

| Endpoint | Purpose | Filters By | Returns |
|----------|---------|------------|---------|
| `GET /customers/active` | All active customers | Client only | All customers who have sent messages |
| `GET /customers/active-for-sender` | **NEW!** Sender's contacts | Sender + Client | Customers who talked WITH a specific sender |
| `GET /channels/by-email` | Sender's conversations | Sender + Client | Channels grouped by recipient |

---

## 1️⃣ GET /customers/active

### Purpose
Lists **ALL** customers who have been active (sent messages), regardless of who they talked to.

### Authentication
Knows the client from the authentication token (`auth('sanctum')->user()`)

### What It Returns
```json
{
    "object": "list",
    "data": [
        {
            "object": "customer",
            "id": "cus_123",
            "name": "Alice",
            "email": "alice@example.com",
            "latest_message_at": 1640995500
        },
        {
            "object": "customer",
            "id": "cus_456",
            "name": "Bob",
            "email": "bob@example.com",
            "latest_message_at": 1640995400
        },
        {
            "object": "customer",
            "id": "cus_789",
            "name": "Charlie",
            "email": "charlie@example.com",
            "latest_message_at": 1640995300
        }
    ]
}
```

### Use Case
**Admin Dashboard** - "Show me all customers who have been messaging on the platform"

### Query Logic
```sql
-- Gets ALL customers who have sent messages
SELECT * FROM customers 
WHERE client_id = ? 
AND EXISTS (SELECT 1 FROM messages WHERE sender_id = customers.id)
ORDER BY (SELECT MAX(created_at) FROM messages WHERE sender_id = customers.id) DESC
```

---

## 2️⃣ GET /customers/active-for-sender ⭐ NEW

### Purpose
Lists customers who have **exchanged messages WITH a specific sender**.

### Authentication
- Client from token (`auth('sanctum')->user()`)
- Sender from query parameter (`?email=...`)

### What It Returns
```json
{
    "object": "list",
    "data": [
        {
            "object": "customer",
            "id": "cus_456",
            "name": "Bob",
            "email": "bob@example.com",
            "latest_message_at": 1640995500  // Last message between Alice & Bob
        },
        {
            "object": "customer",
            "id": "cus_789",
            "name": "Charlie",
            "email": "charlie@example.com",
            "latest_message_at": 1640995300  // Last message between Alice & Charlie
        }
    ]
}
```

**Note:** If Alice hasn't talked to anyone, this returns an empty list.

### Use Case
**Personalized Sidebar** - "I'm alice@example.com, show me the people I've been chatting with"

### Query Logic
```sql
-- Get channels where Alice participates
channel_ids = SELECT channel_id FROM channel_customer WHERE customer_id = alice_id

-- Get customers who:
-- 1. Share channels with Alice (they're in the same channels)
-- 2. Have sent messages in those channels
-- 3. Are not Alice herself
SELECT DISTINCT customers.* FROM customers
WHERE client_id = ?
AND id != alice_id
AND EXISTS (
    -- They share channels with Alice
    SELECT 1 FROM channel_customer 
    WHERE customer_id = customers.id 
    AND channel_id IN (channel_ids)
)
AND EXISTS (
    -- They've sent messages in those channels
    SELECT 1 FROM messages 
    WHERE sender_id = customers.id 
    AND channel_id IN (channel_ids)
)
ORDER BY (
    SELECT MAX(created_at) FROM messages 
    WHERE channel_id IN (channel_ids)
    AND (sender_id = customers.id OR sender_id = alice_id)
) DESC
```

---

## 3️⃣ GET /channels/by-email

### Purpose
Shows **who** a customer has talked to and **which channels** they used.

### Authentication
- Client from token
- Customer from query parameter (`?email=...`)

### What It Returns
```json
{
    "data": {
        "conversations": [
            {
                "recipient": {
                    "id": "cus_456",
                    "name": "Bob",
                    "email": "bob@example.com"
                },
                "channels": [
                    {
                        "id": "ch_123",
                        "type": "general",
                        "name": "General"
                    },
                    {
                        "id": "ch_124",
                        "type": "custom",
                        "name": "Project Discussion"
                    }
                ],
                "latest_message_at": 1640995500
            },
            {
                "recipient": {
                    "id": "cus_789",
                    "name": "Charlie",
                    "email": "charlie@example.com"
                },
                "channels": [
                    {
                        "id": "ch_125",
                        "type": "general",
                        "name": "General"
                    }
                ],
                "latest_message_at": 1640995300
            }
        ]
    }
}
```

### Use Case
**Detailed Conversation View** - "Show me who Alice has talked to and in which channels"

---

## 🎯 When To Use Which?

### Use Case 1: Admin Support Dashboard
**Goal:** See all active customers on the platform

**Use:**
```javascript
// Get ALL active customers
const allActive = await sdk.getActiveCustomers({ limit: 50 });
// Returns: Alice, Bob, Charlie, David, Eve...
```

---

### Use Case 2: Personal Messaging Interface (Like WhatsApp)
**Goal:** User wants to see only their own conversations

**Use:**
```javascript
// Get customers I've talked to
const myContacts = await sdk.getActiveCustomersForSender(
    'alice@example.com', 
    { limit: 50 }
);
// Returns: Only Bob and Charlie (people Alice talked to)
```

---

### Use Case 3: Conversation Details
**Goal:** See all channels with a specific person

**Use:**
```javascript
// Get all my conversations grouped by person
const conversations = await sdk.getChannelsByEmail('alice@example.com');
// Returns: Bob (General + Project channels), Charlie (General channel)
```

---

## 💡 Real-World Example

### Scenario: Alice's Messaging App

```javascript
class AliceMessagingApp {
    async loadMySidebar() {
        // Get people Alice has talked to
        const myContacts = await sdk.getActiveCustomersForSender(
            'alice@example.com',
            { limit: 50 }
        );
        
        // Display in sidebar
        this.renderSidebar(myContacts.data);
        // Shows: Bob, Charlie
    }
    
    async selectContact(email) {
        // Load conversation with selected person
        const messages = await sdk.getMessagesBetweenCustomers(
            'alice@example.com',
            email,
            { limit: 50 }
        );
        
        this.renderMessages(messages.data);
    }
}
```

---

## 📝 API Request Examples

### Example 1: All Active Customers (Admin View)

```bash
curl -X GET "https://api.example.com/api/v1/customers/active?limit=20" \
  -H "Authorization: Bearer sk_test_123" \
  -H "X-Public-Key: pk_test_123" \
  -H "Origin: https://example.com"
```

**Returns:** All customers who have sent messages

---

### Example 2: Alice's Contacts (User View) ⭐ NEW

```bash
curl -X GET "https://api.example.com/api/v1/customers/active-for-sender?email=alice@example.com&limit=20" \
  -H "Authorization: Bearer sk_test_123" \
  -H "X-Public-Key: pk_test_123" \
  -H "Origin: https://example.com"
```

**Returns:** Only customers who have exchanged messages with Alice

**Note:** The email is automatically converted to lowercase on the server for consistent querying.

---

### Example 3: Alice's Conversations (Grouped View)

```bash
curl -X GET "https://api.example.com/api/v1/channels/by-email?email=alice@example.com" \
  -H "Authorization: Bearer sk_test_123" \
  -H "X-Public-Key: pk_test_123" \
  -H "Origin: https://example.com"
```

**Returns:** Alice's conversations grouped by recipient with all channels

**Note:** The email is automatically converted to lowercase on the server for consistent querying.

---

## 🔐 Authentication Flow

All endpoints authenticate the **client** automatically:

```php
// In the controller
$client = auth('sanctum')->user(); // Gets the authenticated client

// Repository/Service uses this client to filter results
// Only returns data belonging to this client
```

The **sender email** is:
- Implicit in `/customers/active` (all customers for the client)
- Explicit in `/customers/active-for-sender/{email}` (specific sender)
- Explicit in `/channels/by-email/{email}` (specific customer)

---

## 🎨 UI/UX Design Patterns

### Pattern 1: Support Agent Dashboard
```
┌─────────────────────────────────┐
│ All Active Customers            │ ← Use: GET /customers/active
├─────────────────────────────────┤
│ □ Alice (5m ago)                │
│ □ Bob (10m ago)                 │
│ □ Charlie (1h ago)              │
│ □ David (2h ago)                │
└─────────────────────────────────┘
```

### Pattern 2: Personal Messaging App (WhatsApp-style)
```
┌─────────────────────────────────┐
│ My Conversations                │ ← Use: GET /customers/active-for-sender/{email}
├─────────────────────────────────┤
│ □ Bob (5m ago)                  │ ← Only people I talked to
│ □ Charlie (1h ago)              │
└─────────────────────────────────┘
```

### Pattern 3: Detailed Conversation Manager
```
┌─────────────────────────────────┐
│ Conversations with Bob          │ ← Use: GET /channels/by-email/{email}
├─────────────────────────────────┤
│ • General (5m ago)              │
│ • Project Discussion (1h ago)   │
│ • Support (2h ago)              │
└─────────────────────────────────┘
```

---

## ✅ Summary

| I want to... | Use this endpoint | Returns |
|-------------|-------------------|---------|
| See all active customers on the platform | `GET /customers/active` | Everyone who sent messages |
| See people I've been chatting with | `GET /customers/active-for-sender/{my_email}` | My conversation partners |
| See all channels with a specific person | `GET /channels/by-email/{my_email}` | Channels grouped by recipient |
| Load messages with someone | `GET /messages/between/{email1}/{email2}` | All messages between us |
| Send a message to someone | `POST /messages/send-to-customer` | Creates/finds channel & sends |

---

## 🚀 JavaScript SDK Usage

```javascript
// Initialize SDK
const sdk = new SlimeTalksSDK({
    apiUrl: 'https://api.example.com/api/v1',
    secretKey: 'sk_test_123',
    publicKey: 'pk_test_123',
    origin: 'https://example.com'
});

// Admin Dashboard: Get all active customers
const allActive = await sdk.getActiveCustomers({ limit: 50 });

// User Dashboard: Get my conversation partners
const myContacts = await sdk.getActiveCustomersForSender(
    'alice@example.com', 
    { limit: 50 }
);

// Detailed View: Get my conversations grouped by person
const myConversations = await sdk.getChannelsByEmail('alice@example.com');

// Load messages with someone
const messages = await sdk.getMessagesBetweenCustomers(
    'alice@example.com',
    'bob@example.com',
    { limit: 50 }
);

// Send a message
await sdk.sendToCustomer({
    sender_email: 'alice@example.com',
    recipient_email: 'bob@example.com',
    type: 'text',
    content: 'Hello Bob!'
});
```

---

**The key difference:** 
- `GET /customers/active` = **Everyone** who's active
- `GET /customers/active-for-sender/{email}` = **My contacts** (people I've talked to) ⭐
- `GET /channels/by-email/{email}` = **My conversations** (detailed channel view)
