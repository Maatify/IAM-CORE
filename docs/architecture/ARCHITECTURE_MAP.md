# 🧠 MAATIFY IAM v1 — Complete Architectural Map

## System Role

IAM هو:

> **Identity Authority Service**

مسؤول فقط عن:

* Authentication
* Session lifecycle
* Identity isolation
* Token issuance

ولا يعرف أي شيء عن:

* business permissions
* wallets
* orders
* application logic

كما تم التأكيد في التصميم الأمني:

IAM يعزل بالكامل:

* tenants
* clients
* actors
* sessions

ولا يعرف أي بيانات business domain.

---

# 🏛 High Level Architecture

```
                +----------------------+
                |  Applications        |
                |----------------------|
                | Admin Panel          |
                | Mobile Apps          |
                | Website              |
                | Internal Services    |
                +----------+-----------+
                           |
                           | API
                           v
                +----------------------+
                |      IAM API         |
                |----------------------|
                | Authentication       |
                | Registration         |
                | Session Control      |
                | JWKS Distribution    |
                +----------+-----------+
                           |
                           v
                +----------------------+
                |   IAM Domain Core    |
                |----------------------|
                | Identity Engine      |
                | Session Lifecycle    |
                | Token Issuance       |
                | Actor Management     |
                +----------+-----------+
                           |
                           v
                +----------------------+
                |   Infrastructure     |
                |----------------------|
                | Crypto Engine        |
                | Database             |
                | Key Store            |
                | Rate Limiter         |
                +----------------------+
```

---

# 🗄 Database Domain Map

## Core Entities

```
Tenant
   │
   ├── Clients
   │
   └── Actors
         │
         ├── Actor Identifiers
         │
         ├── Actor Credentials
         │
         └── Sessions
```

---

# Entity Relationship

## Tenant

يمثل نظام مستقل.

```
iam_tenants
```

كل شيء في IAM مربوط بالـ tenant.

---

## Client

يمثل التطبيق الذي يستخدم IAM.

```
iam_clients
```

أمثلة:

* admin-panel
* mobile-app
* website
* backend-api

كل Client مرتبط بـ Tenant.

Sessions دائمًا مرتبطة بـ client.

---

## Actor

يمثل هوية قابلة لتسجيل الدخول.

```
iam_actors
```

أمثلة actor types:

```
USER
ADMIN
MERCHANT
SERVICE
```

---

## Actor Identifiers

المعرفات لا يتم تخزينها plaintext.

يتم تخزينها باستخدام:

```
Identifier
   │
   ├── Encrypted Value
   └── Blind Index
```

الجدول:

```
iam_actor_identifiers
```

البيانات المخزنة:

```
lookup_hash  → HMAC blind index
cipher       → encrypted value
iv           → AES nonce
tag          → AES GCM tag
key_id       → encryption key
```

هذا يمنع:

* identifier enumeration
* plaintext leaks

كما يحتوي الجدول على:

```
is_verified
```

مما يسمح بتنفيذ verification flows لاحقًا.

---

## Actor Credentials

طرق تسجيل الدخول.

```
iam_actor_credentials
```

الأنواع الممكنة:

```
PASSWORD
OAUTH_GOOGLE
OAUTH_MICROSOFT
```

لو كان النوع PASSWORD:

```
Argon2id hash
+ Pepper
```

---

## Sessions

الجلسات.

```
iam_sessions
```

تحتوي على:

```
refresh_token_hash
device_id
ip
user_agent
client_id
expires_at
revoked_at
```

وتدعم:

* refresh token rotation
* device binding
* global logout

---

# 🔐 Cryptographic Architecture

## Algorithms

| Purpose          | Algorithm   |
| ---------------- | ----------- |
| PII encryption   | AES-256-GCM |
| Blind index      | HMAC-SHA256 |
| JWT signing      | Ed25519     |
| Password hashing | Argon2id    |

---

# 🔑 Key Management Model

كل استخدام له **key ring منفصل**.

```
Key Rings
│
├── PII_ENC
├── JWT_SIGN
├── LOOKUP_HMAC
├── MFA_TOTP
└── ABUSE_HMAC
```

هذا يقلل:

```
blast radius
```

لو تم تسريب مفتاح.

---

# 🔐 Token Architecture

## Access Token

```
JWT
```

claims:

```
sub
tenant_id
actor_type
client_id
session_id
exp
iat
kid
```

TTL:

```
5 – 15 minutes
```

JWT يتم توقيعه باستخدام:

```
Ed25519
```

والمفتاح المستخدم يظهر عبر:

```
JWKS endpoint
```

---

## Refresh Token

```
opaque random token
```

يتم تخزينه كالتالي:

```
hash(token)
```

في:

```
iam_sessions.refresh_token_hash
```

---

## Refresh Rotation

كل عملية refresh تقوم بـ:

```
old refresh → invalid
new refresh → issued
```

مع:

```
grace window ≈ 10 seconds
```

---

# 🔄 Session Lifecycle

## Session Creation

```
Login
   │
   ├── verify credentials
   ├── create session
   ├── generate refresh token
   └── issue access token
```

---

## Token Refresh

```
Refresh Request
   │
   ├── validate refresh token
   ├── rotate refresh token
   ├── update session
   └── issue new access token
```

---

## Session Revocation

```
Revoke Session
   │
   └── set revoked_at
```

---

# 🚨 Abuse Protection

أنظمة الحماية:

```
Rate limit
per IP
per tenant
per identifier
```

مع:

```
exponential backoff
```

---

# 🧱 Security Boundaries

IAM يعزل:

```
Tenant
Client
Actor Type
Session
Crypto Keys
```

ولا يعرف:

```
Permissions
Wallets
Orders
Business State
```

هذا جزء أساسي من تصميم العزل في IAM.

---

# 🌐 API Surface

## Public Endpoints (Client Facing)

هذه endpoints تستخدمها التطبيقات.

```
POST /auth/register
POST /auth/login
POST /auth/refresh
POST /auth/logout
GET  /.well-known/jwks.json
```

كل Public endpoint يجب أن يحدد:

```
client_key
```

أو أي آلية تحدد:

```
client_id
```

لأن:

```
sessions مرتبطة دائمًا بـ client
```

---

## Internal Privileged Endpoints

تستخدم فقط داخل الشبكة الداخلية.

```
POST /internal/actors/provision
```

هذه تستخدم من:

```
Admin Panel
Internal Services
Provisioning Systems
```

ويتم حمايتها عبر:

```
trusted network
mTLS
private routing
```

---

# 🧠 IAM Complete Runtime Architecture

```
                Applications
      (Mobile / Website / Admin Panel)
                        │
                        ▼
                +----------------+
                |     IAM API    |
                +----------------+
                        │
      ┌─────────────────┼─────────────────┐
      ▼                 ▼                 ▼
 Registration        Login            Refresh
 Actor Creation   Session Start      Token Rotate
      │                 │                 │
      ▼                 ▼                 ▼
 Actor + Identifier   Session           Session
 + Credential         Creation          Update
```

---

# 1️⃣ Self Registration Flow

هذا يحدث عندما المستخدم يسجل من التطبيق.

## Endpoint

```
POST /auth/register
```

---

## Flow

```
User
 │
 ▼
POST /auth/register
 │
 ▼
Validate request
 │
 ▼
Check identifier existence
 │
 ▼
Create Actor
 │
 ▼
Create Identifier
 │
 ▼
Create Credential
 │
 ▼
Return success
```

---

## Registration Verification

الـ identifier يبدأ عادة:

```
is_verified = 0
```

ويحتاج verification flow لاحقًا مثل:

```
email verification
phone OTP
```

بعدها يصبح:

```
is_verified = 1
```

---

# 2️⃣ Admin Provisioning Flow

في بعض الأنظمة:

المستخدم لا يسجل بنفسه.

بل يتم إنشاؤه من الإدارة.

## Endpoint

```
POST /internal/actors/provision
```

---

## Flow

```
Admin Panel
     │
     ▼
Provision Actor
     │
     ▼
Create Actor
     │
     ▼
Create Identifier
     │
     ▼
Create Credential
```

الفرق عن self-register:

| Step          | Self Register  | Admin Provision            |
| ------------- | -------------- | -------------------------- |
| Authorization | none           | privileged client          |
| endpoint      | /auth/register | /internal/actors/provision |

لكن **database logic واحد**.

---

# 3️⃣ Login Flow

```
POST /auth/login
```

```
lookup identifier hash
       │
       ▼
decrypt identifier
       │
       ▼
verify credential
       │
       ▼
create session
       │
       ▼
issue tokens
```

---

# 4️⃣ Session Creation

بعد login:

```
create iam_sessions
```

ويتم إنشاء:

```
refresh_token_hash
device_id
ip
user_agent
client_id
```

---

# 5️⃣ Refresh Flow

```
POST /auth/refresh
```

```
validate refresh token
       │
       ▼
rotate refresh token
       │
       ▼
issue new access token
```

---

# 6️⃣ Logout Flow

```
POST /auth/logout
```

```
update iam_sessions
set revoked_at
```

---

# 🧩 Complete Identity Lifecycle

```
Registration
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

# 📌 أهم ملاحظة معمارية

IAM ليس:

```
User System
Business Backend
Wallet Engine
Permission Engine
```

IAM هو فقط:

```
Identity Authority
Session Authority
Token Authority
Isolation Boundary
```

كل شيء آخر يتم في الأنظمة التي تستخدم IAM.

---
