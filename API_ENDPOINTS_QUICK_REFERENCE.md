# API Endpoints Quick Reference

## 🚀 Complete Endpoint List

All endpoints with their correct formats after the email parameter updates.

---

## 🔐 Authentication

All endpoints require these headers:
```
Authorization: Bearer {token}
X-Public-Key: {public_key}
Origin: {domain}
```

---

## 📋 Customer Management Endpoints

### 1. Create Customer
```
POST /api/v1/customers
Body: { name, email, metadata? }
```

### 2. Get Customer
```
GET /api/v1/customers/{customer_uuid}
```

### 3. List Customers
```
GET /api/v1/customers?limit={limit}&starting_after={uuid}
```

### 4. Get Active Customers (All)
```
GET /api/v1/customers/active?limit={limit}&starting_after={uuid}
```
**Returns:** All customers who have sent messages

### 5. Get Active Customers For Sender ⭐ NEW
```
GET /api/v1/customers/active-for-sender?email={sender_email}&limit={limit}
```
**Returns:** Customers who have exchanged messages with the specified sender

---

## 📢 Channel Management Endpoints

### 6. Create Channel
```
POST /api/v1/channels
Body: { type, customer_uuids, name? }
```

### 7. Get Channel
```
GET /api/v1/channels/{channel_uuid}
```

### 8. List Channels
```
GET /api/v1/channels?limit={limit}&starting_after={uuid}
```

### 9. Get Customer Channels
```
GET /api/v1/channels/customer/{customer_uuid}
```

### 10. Get Channels by Email (Grouped by Recipient)
```
GET /api/v1/channels/by-email/{email}
```
**Returns:** Channels grouped by conversation partner

---

## 💬 Message Management Endpoints

### 11. Send Message
```
POST /api/v1/messages
Body: { channel_uuid, sender_uuid, type, content, metadata? }
```

### 12. Get Channel Messages
```
GET /api/v1/messages/channel/{channel_uuid}?limit={limit}
```

### 13. Get Customer Messages
```
GET /api/v1/messages/customer/{customer_uuid}?limit={limit}
```

### 14. Get Messages Between Customers ⭐ UPDATED
```
GET /api/v1/messages/between?email1={email1}&email2={email2}&limit={limit}
```
**Returns:** All messages between two customers across all channels

### 15. Send Message to Customer
```
POST /api/v1/messages/send-to-customer
Body: { sender_email, recipient_email, type, content, metadata? }
```
**Auto-creates/finds general channel**

---

## 🎯 Customer-Centric Messaging Workflow

### Use Case: WhatsApp-Style Interface

```javascript
// Step 1: Load my conversation partners
const contacts = await sdk.getActiveCustomersForSender(
    'alice@example.com',  // My email
    { limit: 50 }
);
// Returns: [Bob, Charlie, David] - People Alice has talked to

// Step 2: User selects Bob from the sidebar
const messages = await sdk.getMessagesBetweenCustomers(
    'alice@example.com',  // My email
    'bob@example.com',    // Selected contact
    { limit: 50 }
);
// Returns: All messages between Alice and Bob

// Step 3: Send a new message
await sdk.sendToCustomer({
    sender_email: 'alice@example.com',
    recipient_email: 'bob@example.com',
    type: 'text',
    content: 'Hey Bob!'
});
// Auto-finds/creates general channel and sends message
```

---

## 📊 Endpoint Comparison

| What I Want | Endpoint | Parameters |
|-------------|----------|------------|
| All active customers | `GET /customers/active` | `limit`, `starting_after` |
| **My conversation partners** | `GET /customers/active-for-sender` | `email`, `limit`, `starting_after` |
| Channels grouped by person | `GET /channels/by-email/{email}` | Path: `email` |
| Messages between two people | `GET /messages/between` | `email1`, `email2`, `limit` |
| Send to someone | `POST /messages/send-to-customer` | Body: `sender_email`, `recipient_email` |

---

## 🔄 Email Parameter Format

### Query Parameters (use `?email=...`)
- ✅ `/customers/active-for-sender?email=alice@example.com`
- ✅ `/messages/between?email1=alice@example.com&email2=bob@example.com`

### Path Parameters (use `/{email}`)
- ✅ `/channels/by-email/alice@example.com`

### POST Body Parameters
- ✅ `/messages/send-to-customer` with JSON body

---

## 🔤 Lowercase Conversion

All email parameters are automatically converted to lowercase on the server:

| Input | Server Receives | Database Query |
|-------|----------------|----------------|
| `Alice@Example.COM` | `Alice@Example.COM` | `alice@example.com` |
| `BOB@EXAMPLE.COM` | `BOB@EXAMPLE.COM` | `bob@example.com` |
| `alice@example.com` | `alice@example.com` | `alice@example.com` |

**Affected Endpoints:**
- ✅ `GET /customers/active-for-sender?email=...`
- ✅ `GET /messages/between?email1=...&email2=...`
- ✅ `POST /messages/send-to-customer` (body emails)

---

## 🧪 Quick Test Commands

### Test 1: Active Customers For Sender
```bash
curl -X GET "https://api.example.com/api/v1/customers/active-for-sender?email=alice@example.com&limit=20" \
  -H "Authorization: Bearer sk_test_123" \
  -H "X-Public-Key: pk_test_123" \
  -H "Origin: https://example.com"
```

### Test 2: Messages Between Customers
```bash
curl -X GET "https://api.example.com/api/v1/messages/between?email1=alice@example.com&email2=bob@example.com&limit=50" \
  -H "Authorization: Bearer sk_test_123" \
  -H "X-Public-Key: pk_test_123" \
  -H "Origin: https://example.com"
```

### Test 3: Case Insensitivity
```bash
curl -X GET "https://api.example.com/api/v1/customers/active-for-sender?email=ALICE@EXAMPLE.COM" \
  -H "Authorization: Bearer sk_test_123" \
  -H "X-Public-Key: pk_test_123" \
  -H "Origin: https://example.com"
# Should work exactly the same as lowercase
```

---

## ✅ SDK Verification Summary

**The SDK IS correctly updated!**

Both methods:
1. ✅ Use query parameters (not URL path)
2. ✅ Build correct query strings with `URLSearchParams`
3. ✅ Match the backend routes exactly
4. ✅ Handle URL encoding automatically
5. ✅ Comments note lowercase conversion on server
6. ✅ Maintain backward-compatible function signatures

**No further SDK changes needed!**

---

## 📝 Files Status

| File | Status | Email Parameters |
|------|--------|-----------------|
| `routes/api.php` | ✅ Updated | Query params |
| `MessageController.php` | ✅ Updated | Query params + lowercase |
| `CustomerController.php` | ✅ Updated | Query params + lowercase |
| `slime-talks-sdk.js` | ✅ Updated | Query params |
| `API_DOCUMENTATION.md` | ✅ Updated | Query params documented |

---

**Everything is in sync and ready to use!** 🎉
