# 🔐 MAATIFY IAM v1 — Security Model

## Document Role

هذا الملف يحدد **النموذج الأمني الرسمي لنظام IAM**.

الهدف:

* تحديد السياسات الأمنية للنظام
* توحيد cryptographic design
* تعريف threat model
* تحديد security boundaries
* توضيح آليات الحماية ضد الهجمات

هذا الملف يعتبر **Security Source of Truth** للنظام.

---

# 1. Security Philosophy

تصميم IAM مبني على عدة مبادئ أساسية:

### Principle 1 — Least Knowledge

IAM لا يعرف أي بيانات business domain.

النظام يعرف فقط:

```text
identity
sessions
tokens
```

ولا يعرف:

```text
wallets
orders
permissions
profiles
business data
```

---

### Principle 2 — Isolation First

كل الكيانات معزولة:

```text
Tenant
Client
Actor
Session
```

لا يمكن لأي request أن تتجاوز هذا العزل.

---

### Principle 3 — Assume Breach

النظام مصمم على افتراض:

> أن بعض المفاتيح قد تتعرض للتسريب.

لذلك يتم:

* فصل key rings
* تقليل blast radius
* دعم key rotation

---

# 2. Threat Model

IAM يجب أن يكون مقاومًا للهجمات التالية.

---

## Credential Stuffing

محاولات تسجيل الدخول باستخدام كلمات مرور مسربة.

الحماية:

```text
rate limit
identifier throttling
ip throttling
```

---

## Brute Force

محاولات تجربة كلمات مرور كثيرة.

الحماية:

```text
rate limiting
exponential backoff
```

---

## Identifier Enumeration

محاولة معرفة ما إذا كان email أو phone مسجل.

الحماية:

```text
blind index lookup
uniform error responses
```

---

## Token Theft

سرقة access token أو refresh token.

الحماية:

```text
short access token TTL
refresh rotation
hashed refresh tokens
session binding
```

---

## Replay Attacks

إعادة استخدام refresh token.

الحماية:

```text
refresh rotation
grace window
session validation
```

---

## Database Breach

تسريب قاعدة البيانات.

الحماية:

```text
encrypted identifiers
hashed passwords
hashed refresh tokens
```

---

# 3. Cryptographic Architecture

IAM يستخدم عدة خوارزميات.

| Purpose          | Algorithm   |
| ---------------- | ----------- |
| PII Encryption   | AES-256-GCM |
| Blind Index      | HMAC-SHA256 |
| JWT Signing      | Ed25519     |
| Password Hashing | Argon2id    |

---

# 4. Identifier Protection

Identifiers مثل:

```text
email
phone
username
```

لا يتم تخزينها plaintext.

---

## Encryption

القيمة يتم تشفيرها باستخدام:

```text
AES-256-GCM
```

---

## Blind Index

يتم إنشاء hash باستخدام:

```text
HMAC-SHA256
```

ويستخدم للبحث.

---

## Stored Fields

```text
cipher
iv
tag
lookup_hash
key_id
```

---

# 5. Password Security

كلمات المرور يتم تخزينها باستخدام:

```text
Argon2id
```

مع:

```text
pepper
```

---

## Password Hash

الهيكل:

```text
Argon2id(password + pepper)
```

---

# 6. Token Security

## Access Tokens

نوع التوكن:

```text
JWT
```

التوقيع:

```text
Ed25519
```

---

## Claims

```json
{
  "sub": "actor_id",
  "tenant_id": "tenant_id",
  "client_id": "client_id",
  "session_id": "session_id",
  "exp": 1700000000,
  "iat": 1699990000,
  "kid": "key_id"
}
```

---

## Token TTL

| Token         | TTL          |
| ------------- | ------------ |
| Access Token  | 5-15 minutes |
| Refresh Token | configurable |

---

# 7. Refresh Token Security

Refresh tokens:

```text
opaque random tokens
```

لا يتم تخزينها plaintext.

---

## Storage

يتم تخزين:

```text
hash(refresh_token)
```

---

## Rotation

كل refresh:

```text
old refresh → invalid
new refresh → issued
```

---

## Grace Window

يسمح بفترة قصيرة:

```text
10 seconds
```

لتفادي race conditions.

---

# 8. Session Security

كل login ينشئ session.

الجدول:

```text
iam_sessions
```

---

## Stored Data

```text
session_id
actor_id
client_id
refresh_token_hash
device_id
ip
user_agent
expires_at
revoked_at
```

---

## Session Revocation

يمكن إلغاء الجلسة عبر:

```text
logout
revoke session
revoke all sessions
```

---

# 9. Key Management

المفاتيح مقسمة إلى rings.

```text
Key Rings
│
├── PII_ENC
├── JWT_SIGN
├── LOOKUP_HMAC
├── MFA_TOTP
└── ABUSE_HMAC
```

---

## Key Rotation

النظام يدعم:

```text
key rotation
key versioning
```

كل مفتاح يحتوي:

```text
key_id
created_at
status
```

---

# 10. Rate Limiting

الحماية من abuse.

---

## Limits

| Scope      | Purpose           |
| ---------- | ----------------- |
| IP         | brute force       |
| identifier | login abuse       |
| client     | API abuse         |
| tenant     | global protection |

---

## Strategy

```text
token bucket
exponential backoff
```

---

# 11. Secure Headers

كل responses يجب أن تحتوي على:

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'none'
```

---

# 12. Internal API Security

بعض endpoints ليست public.

مثل:

```text
/internal/actors/provision
```

هذه endpoints محمية عبر:

```text
trusted network
mTLS
private routing
```

---

# 13. Logging and Monitoring

يجب تسجيل الأحداث الأمنية.

---

## Security Events

```text
login success
login failure
identifier verification
session creation
session revocation
token refresh
rate limit triggered
```

---

## Audit Log

يجب أن يحتوي:

```text
timestamp
actor_id
client_id
ip
event_type
```

---

# 14. Security Boundaries

IAM مسؤول فقط عن:

```text
Identity
Authentication
Sessions
Tokens
```

ولا يجب أن يحتوي:

```text
RBAC
wallet logic
business data
application permissions
```

---

# 15. Security Best Practices

النظام يجب أن يطبق:

```text
HTTPS only
secure random generator
strict input validation
constant-time comparisons
secure key storage
encrypted backups
```

---

# 16. Future Security Extensions

الإصدارات القادمة قد تضيف:

```text
MFA (TOTP)
device trust
risk-based authentication
session anomaly detection
```

---

# End of Document

**IAM v1 Security Model — Official Security Specification**
