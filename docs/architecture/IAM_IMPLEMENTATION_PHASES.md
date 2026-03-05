# 🧠 MAATIFY IAM — Implementation Phases
## Detailed Engineering Plan

---

# Document Role

هذا الملف يحدد **خطة التنفيذ الهندسية التفصيلية لنظام IAM**.

الهدف:

• تحديد الخدمات التي يجب تنفيذها
• تحديد ترتيب التنفيذ
• تحديد المسؤوليات لكل Service
• منع التداخل بين الطبقات
• ضمان التوافق مع Security Model

هذا الملف يعتبر:

> **Engineering Source of Truth**

---

# Current System State

النظام حالياً أنهى:

```

Phase A — Kernel

```

ويتضمن:

• HTTP Kernel
• Error Handling
• Request Tracing
• Trusted Internal Network Gate
• Domain Lookup Foundation

الخطوة التالية هي:

```

Identity Provisioning

```

---

# System Layers

النظام مقسم إلى أربع طبقات رئيسية:

```

Applications
API Layer
Domain Core
Infrastructure

```

طبقة التنفيذ الأساسية هي:

```

Domain Core

```

---

# Domain Core Services

الخدمات الأساسية داخل Domain Core هي:

```

ActorService
IdentifierService
CredentialService
AuthenticationService
SessionService
TokenService
VerificationService
AbuseProtectionService

```

كل خدمة مسؤولة عن جزء محدد من النظام.

---

# Phase B — Actor Provisioning

## الهدف

إنشاء Actor داخل IAM.

---

## Endpoint

```

POST /internal/actors/provision

```

---

## Flow

```

Request
│
▼
validate request
│
▼
canonicalize identifier
│
▼
generate blind index
│
▼
encrypt identifier
│
▼
create actor
│
▼
create identifier
│
▼
create credential
│
▼
return actor_id

```

---

## Services Used

```

ActorService
IdentifierService
CredentialService

```

---

## ActorService Responsibilities

```

createActor
getActor
disableActor
deleteActor

```

يتعامل مع:

```

iam_actors

```

---

## IdentifierService Responsibilities

```

createIdentifier
lookupIdentifier
verifyIdentifier

```

يتعامل مع:

```

iam_actor_identifiers

```

---

## Identifier Security

Identifier يتم تخزينه عبر:

```

AES-256-GCM encryption
HMAC blind index

```

Stored fields:

```

cipher
iv
tag
lookup_hash
key_id

```

---

## CredentialService Responsibilities

```

createPasswordCredential
verifyPassword
linkOAuthCredential

```

يتعامل مع:

```

iam_actor_credentials

```

---

## Password Hashing

الخوارزمية المستخدمة:

```

Argon2id

```

مع:

```

pepper

```

---

# Phase C — Authentication Engine

## الهدف

تمكين login.

---

## Endpoint

```

POST /auth/login

```

---

## Flow

```

lookup identifier
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

## Services Used

```

AuthenticationService
IdentifierService
CredentialService
SessionService
TokenService

```

---

# AuthenticationService

المحرك الأساسي لعملية login.

Responsibilities:

```

authenticateIdentifier
validateCredential
createAuthenticatedSession

```

---

# Phase D — Session Lifecycle

## الهدف

إدارة جلسات المستخدم.

---

## SessionService Responsibilities

```

createSession
validateSession
revokeSession
revokeAllSessions

```

يتعامل مع:

```

iam_sessions

```

---

## Session Creation

يتم إنشاء:

```

session_id
refresh_token_hash
device_id
ip
user_agent
expires_at

```

---

# Phase E — Token Infrastructure

## TokenService Responsibilities

```

issueAccessToken
validateAccessToken
rotateRefreshToken

```

---

## Access Token

نوع التوكن:

```

JWT

```

الخوارزمية:

```

Ed25519

```

---

## Claims

```

sub
tenant_id
client_id
session_id
actor_type
exp
iat
kid

```

---

# Phase F — Refresh Token Rotation

Endpoint:

```

POST /auth/refresh

```

---

Flow:

```

validate refresh token
rotate refresh token
update session
issue new access token

```

---

# Phase G — Logout

Endpoint:

```

POST /auth/logout

```

---

Flow:

```

validate session
revoke session

```

---

# Phase H — Verification System

## الهدف

تأكيد identifier.

---

## VerificationService Responsibilities

```

sendVerificationEmail
verifyEmail
sendOTP
verifyPhone

```

---

## Result

```

identifier.is_verified = 1

```

---

# Phase I — Abuse Protection

## AbuseProtectionService

يحمي النظام من الهجمات.

---

## Features

```

rate limiting
identifier throttling
ip throttling
tenant throttling

```

---

## Protection Targets

```

login
register
refresh
verification

```

---

# Phase J — Monitoring & Audit

يجب تسجيل الأحداث الأمنية.

---

## Logged Events

```

login success
login failure
session creation
session revocation
token refresh
rate limit triggered

```

---

# Phase K — JWKS Infrastructure

Endpoint:

```

GET /.well-known/jwks.json

```

يعرض مفاتيح توقيع JWT.

---

# Phase L — Production Hardening

---

## Infrastructure Security

```

mTLS internal APIs
private network routing
secure key storage
encrypted backups

```

---

## Security Headers

```

X-Content-Type-Options
X-Frame-Options
Content-Security-Policy

```

---

# Final Architecture

```

Applications
│
▼
IAM API Layer
│
▼
Domain Core
│
▼
Infrastructure

```

---

# Production Definition

النظام يصبح Production Ready عندما يتم تنفيذ:

```

Provisioning
Authentication
Sessions
Tokens
Verification
Abuse Protection
Monitoring
Security Hardening

```

---

# End of Document
