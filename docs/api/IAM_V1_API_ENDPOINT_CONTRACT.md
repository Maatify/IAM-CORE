# IAM v1 — Complete Endpoint Contract (Production Ready)

## Document Role

هذا الملف هو **العقد الرسمي لكل IAM API endpoints**.

الهدف:

* منع أي تخمين أثناء تنفيذ الـ API
* توحيد request/response structure
* تحديد security requirements
* تحديد error model

---

# 1. API Base Configuration

## Base URL

```text
https://iam.example.com
```

---

## Content Type

كل الطلبات:

```http
Content-Type: application/json
```

كل الردود:

```http
Content-Type: application/json
```

---

# 2. Authentication Model

## Client Identification

كل Public API يجب أن يحدد client.

يتم ذلك عبر:

```http
X-Client-Key: <client_key>
```

يتم استخدام هذا المفتاح لتحديد:

```text
client_id
tenant_id
```

---

# 3. Error Response Model

كل الأخطاء تستخدم نفس البنية.

```json
{
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Invalid login credentials",
    "request_id": "req_xxxxxx"
  }
}
```

---

## Error Codes

| Code                    | Meaning                   |
| ----------------------- | ------------------------- |
| INVALID_CREDENTIALS     | login failed              |
| IDENTIFIER_EXISTS       | identifier already exists |
| IDENTIFIER_NOT_FOUND    | identifier not found      |
| IDENTIFIER_NOT_VERIFIED | identifier not verified   |
| INVALID_TOKEN           | token invalid             |
| TOKEN_EXPIRED           | token expired             |
| SESSION_REVOKED         | session revoked           |
| RATE_LIMITED            | too many requests         |
| UNAUTHORIZED_CLIENT     | client invalid            |
| FORBIDDEN               | insufficient permission   |

---

# 4. Public Authentication Endpoints

## Register Actor

### Endpoint

```http
POST /auth/register
```

---

### Headers

```http
X-Client-Key: client_key
```

---

### Request

```json
{
  "identifier": "user@example.com",
  "identifier_type": "EMAIL",
  "password": "StrongPassword123!"
}
```

---

### Validation Rules

| Field           | Rule                     |
| --------------- | ------------------------ |
| identifier      | required                 |
| identifier_type | EMAIL / PHONE / USERNAME |
| password        | strong password          |

---

### Response

```json
{
  "actor_id": "act_123456",
  "identifier_verified": false
}
```

---

## Login

### Endpoint

```http
POST /auth/login
```

---

### Headers

```http
X-Client-Key: client_key
```

---

### Request

```json
{
  "identifier": "user@example.com",
  "password": "StrongPassword123!"
}
```

---

### Response

```json
{
  "access_token": "jwt_token",
  "refresh_token": "opaque_refresh_token",
  "expires_in": 900
}
```

---

### Access Token Claims

```json
{
  "sub": "actor_id",
  "tenant_id": "tenant_id",
  "client_id": "client_id",
  "session_id": "session_id",
  "actor_type": "USER",
  "exp": 1700000000,
  "iat": 1699990000,
  "kid": "key_id"
}
```

---

## Refresh Token

### Endpoint

```http
POST /auth/refresh
```

---

### Request

```json
{
  "refresh_token": "opaque_refresh_token"
}
```

---

### Response

```json
{
  "access_token": "jwt_token",
  "refresh_token": "new_refresh_token",
  "expires_in": 900
}
```

---

### Behavior

Refresh token rotation:

```text
old refresh → invalid
new refresh → issued
```

Grace window:

```text
10 seconds
```

---

## Logout

### Endpoint

```http
POST /auth/logout
```

---

### Headers

```http
Authorization: Bearer <access_token>
```

---

### Response

```json
{
  "status": "logged_out"
}
```

---

# 5. Session Management

## List Sessions

### Endpoint

```http
GET /sessions
```

---

### Headers

```http
Authorization: Bearer <access_token>
```

---

### Response

```json
{
  "sessions": [
    {
      "session_id": "sess_123",
      "device_id": "device_abc",
      "ip": "192.168.1.1",
      "user_agent": "Chrome",
      "created_at": "2025-01-01T00:00:00Z",
      "expires_at": "2025-01-02T00:00:00Z"
    }
  ]
}
```

---

## Revoke Session

### Endpoint

```http
POST /sessions/revoke
```

---

### Request

```json
{
  "session_id": "sess_123"
}
```

---

### Response

```json
{
  "status": "revoked"
}
```

---

## Revoke All Sessions

### Endpoint

```http
POST /sessions/revoke-all
```

---

### Response

```json
{
  "status": "all_sessions_revoked"
}
```

---

# 6. Internal Provisioning API

هذه endpoints متاحة فقط داخل الشبكة الداخلية.

---

## Provision Actor

### Endpoint

```http
POST /internal/actors/provision
```

---

### Security

يجب أن يكون:

* trusted network
* internal client
* mTLS أو private routing

---

### Request

```json
{
  "actor_type": "ADMIN",
  "identifier": "admin@example.com",
  "identifier_type": "EMAIL",
  "password": "StrongPassword123!"
}
```

---

### Response

```json
{
  "actor_id": "act_admin_123"
}
```

---

# 7. JWKS Endpoint

## Endpoint

```http
GET /.well-known/jwks.json
```

---

### Response

```json
{
  "keys": [
    {
      "kty": "OKP",
      "crv": "Ed25519",
      "kid": "key_123",
      "x": "public_key_value"
    }
  ]
}
```

---

# 8. Rate Limiting

يتم تطبيق limits على:

| Scope      | Limit                  |
| ---------- | ---------------------- |
| IP         | brute force protection |
| identifier | login abuse            |
| client     | API abuse              |
| tenant     | system protection      |

---

# 9. Security Requirements

النظام يستخدم:

| Feature               | Implementation |
| --------------------- | -------------- |
| password hashing      | Argon2id       |
| identifier encryption | AES-256-GCM    |
| blind index           | HMAC-SHA256    |
| JWT signing           | Ed25519        |
| refresh tokens        | hashed         |

---

# 10. HTTP Status Codes

| Status | Meaning      |
| ------ | ------------ |
| 200    | success      |
| 201    | created      |
| 400    | bad request  |
| 401    | unauthorized |
| 403    | forbidden    |
| 404    | not found    |
| 409    | conflict     |
| 429    | rate limited |
| 500    | server error |

---

# 11. Security Headers

كل responses يجب أن تحتوي على:

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'none'
```

---

# 12. Token TTL

| Token         | TTL          |
| ------------- | ------------ |
| Access Token  | 5–15 minutes |
| Refresh Token | configurable |
| Session       | configurable |

---

# 13. Identity Lifecycle

```text
Register
    │
    ▼
Actor Created
    │
    ▼
Login
    │
    ▼
Session Created
    │
    ▼
Access Token Issued
    │
    ▼
Refresh Rotation
    │
    ▼
Session Revocation
```

---

# 14. Architectural Boundary

IAM مسؤول فقط عن:

```text
Identity
Authentication
Sessions
Tokens
```

ولا يعرف أي شيء عن:

```text
permissions
profiles
wallets
orders
business logic
```

---

# End of Document

**IAM v1 API Contract — Production Ready**
