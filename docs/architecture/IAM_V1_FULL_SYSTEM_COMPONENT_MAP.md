# 🧠 MAATIFY IAM v1 — Full System Component Map

## System Identity

IAM هو:

> **Central Identity Authority**

وظيفته إصدار وإدارة:

* identities
* sessions
* tokens

ولا يتعامل مع:

* business logic
* permissions
* wallets
* orders

---

# 🏛 System Layered Architecture

النظام مقسم إلى أربع طبقات واضحة.

```text
Applications
     │
     ▼
API Layer
     │
     ▼
Domain Core
     │
     ▼
Infrastructure
```

---

# 🌐 Layer 1 — Applications

الأنظمة التي تستخدم IAM.

```text
Applications
│
├── Admin Panel
├── Mobile Apps
├── Website
└── Internal Services
```

هذه الأنظمة **لا تنفذ authentication داخليًا**
بل تعتمد على IAM.

---

# 🌐 Layer 2 — IAM API Layer

هذه الطبقة تعرض HTTP endpoints.

```text
IAM API
│
├── Auth Controller
├── Session Controller
├── Actor Controller
└── JWKS Controller
```

---

## Auth Controller

```text
POST /auth/register
POST /auth/login
POST /auth/refresh
POST /auth/logout
```

---

## Session Controller

```text
GET  /sessions
POST /sessions/revoke
POST /sessions/revoke-all
```

---

## Actor Controller (Internal)

```text
POST /internal/actors/provision
```

---

## JWKS Controller

```text
GET /.well-known/jwks.json
```

---

# 🧠 Layer 3 — Domain Core

هذه الطبقة هي **محرك IAM الحقيقي**.

تحتوي الخدمات الأساسية.

```text
Domain Core
│
├── Actor Service
├── Credential Service
├── Authentication Service
├── Session Service
├── Token Service
├── Identifier Service
├── Verification Service
└── Abuse Protection Service
```

---

# 🔑 Actor Service

مسؤول عن إدارة الهوية الأساسية.

```text
ActorService
```

وظائفه:

```text
createActor
getActor
disableActor
deleteActor
```

يتعامل مع:

```text
iam_actors
```

---

# 🪪 Identifier Service

يدير identifiers مثل:

* email
* phone
* username

```text
IdentifierService
```

وظائفه:

```text
createIdentifier
lookupIdentifier
verifyIdentifier
```

يتعامل مع:

```text
iam_actor_identifiers
```

---

# 🔐 Credential Service

مسؤول عن كلمات المرور وطرق المصادقة.

```text
CredentialService
```

وظائفه:

```text
createPasswordCredential
verifyPassword
linkOAuth
```

يتعامل مع:

```text
iam_actor_credentials
```

---

# 🔑 Authentication Service

المحرك الرئيسي لعملية login.

```text
AuthenticationService
```

Flow:

```text
identifier lookup
credential verify
session creation
token issuance
```

---

# 🧭 Session Service

مسؤول عن إدارة الجلسات.

```text
SessionService
```

وظائفه:

```text
createSession
revokeSession
revokeAllSessions
validateSession
```

يتعامل مع:

```text
iam_sessions
```

---

# 🎟 Token Service

مسؤول عن إصدار JWT.

```text
TokenService
```

وظائفه:

```text
issueAccessToken
validateAccessToken
refreshToken
rotateRefreshToken
```

التوقيع:

```text
Ed25519
```

---

# 🧩 Verification Service

مسؤول عن verification flows.

```text
VerificationService
```

مثل:

```text
email verification
phone OTP
```

ويقوم بتحديث:

```text
iam_actor_identifiers.is_verified
```

---

# 🚨 Abuse Protection Service

يحمي النظام من الهجمات.

```text
AbuseProtectionService
```

يتعامل مع:

```text
rate limiting
identifier lock
ip lock
tenant throttling
```

---

# ⚙ Layer 4 — Infrastructure

هذه الطبقة تحتوي الأدوات التقنية.

```text
Infrastructure
│
├── Database Layer
├── Crypto Engine
├── Key Management
├── Rate Limiter
└── Secure Random Generator
```

---

# 🗄 Database Layer

يوفر repositories للوصول للبيانات.

```text
Repositories
│
├── ActorRepository
├── IdentifierRepository
├── CredentialRepository
├── SessionRepository
├── ClientRepository
└── TenantRepository
```

---

# 🔐 Crypto Engine

المسؤول عن العمليات التشفيرية.

```text
Crypto Engine
```

الوظائف:

```text
encryptPII
decryptPII
generateBlindIndex
secureRandom
```

الخوارزميات:

```text
AES-256-GCM
HMAC-SHA256
```

---

# 🔑 Key Management

يدير المفاتيح.

```text
KeyManager
```

Key Rings:

```text
PII_ENC
JWT_SIGN
LOOKUP_HMAC
MFA_TOTP
ABUSE_HMAC
```

---

# 🚦 Rate Limiter

يحمي النظام من brute force.

```text
RateLimiter
```

يطبق حدودًا على:

```text
IP
identifier
tenant
client
```

---

# 🔄 Complete Login Flow (Component Level)

```text
Login Request
     │
     ▼
Auth Controller
     │
     ▼
AuthenticationService
     │
     ▼
IdentifierService
     │
     ▼
CredentialService
     │
     ▼
SessionService
     │
     ▼
TokenService
     │
     ▼
Return Tokens
```

---

# 🔄 Registration Flow

```text
Register Request
     │
     ▼
Auth Controller
     │
     ▼
ActorService
     │
     ▼
IdentifierService
     │
     ▼
CredentialService
     │
     ▼
Return Actor
```

---

# 🔄 Refresh Flow

```text
Refresh Request
     │
     ▼
Auth Controller
     │
     ▼
SessionService
     │
     ▼
TokenService
     │
     ▼
Rotate Refresh
     │
     ▼
Issue Access Token
```

---

# 🔄 Logout Flow

```text
Logout Request
     │
     ▼
SessionController
     │
     ▼
SessionService
     │
     ▼
revokeSession
```

---

# 🧩 Final Component Map

```text
IAM System
│
├── API Layer
│     ├── AuthController
│     ├── SessionController
│     ├── ActorController
│     └── JWKSController
│
├── Domain Core
│     ├── ActorService
│     ├── IdentifierService
│     ├── CredentialService
│     ├── AuthenticationService
│     ├── SessionService
│     ├── TokenService
│     ├── VerificationService
│     └── AbuseProtectionService
│
└── Infrastructure
      ├── Repositories
      ├── CryptoEngine
      ├── KeyManager
      ├── RateLimiter
      └── SecureRandom
```

---

# 📌 الخلاصة المعمارية

IAM v1 يتكون من:

### 4 طبقات

1️⃣ Applications
2️⃣ API Layer
3️⃣ Domain Core
4️⃣ Infrastructure

---

# أهم مبدأ في التصميم

IAM مسؤول فقط عن:

```text
Identity
Authentication
Sessions
Tokens
```

وليس مسؤولًا عن:

```text
Permissions
Profiles
Wallets
Orders
Business Logic
```

---
