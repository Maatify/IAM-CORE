# 🚀 MAATIFY IAM — Execution Roadmap
## From Kernel → Production System

---

# Document Role

هذا الملف يحدد **خارطة التنفيذ الكاملة لنظام IAM** من المرحلة الحالية
حتى الوصول إلى **Production Ready Identity Provider**.

الهدف:

- توضيح ما تم تنفيذه
- تحديد المرحلة الحالية
- تحديد الخطوات القادمة بوضوح
- منع أي انحراف في التنفيذ

---

# 🧠 Current System Status

النظام حالياً أنهى:

> **Phase A — Kernel Foundation**

ويتضمن:

- HTTP Kernel
- Error Handling
- Request Tracing
- Internal Security Gate
- Domain Lookup Foundation

---

# 🏛 Phase A — IAM Kernel (COMPLETED)

## الهدف

إنشاء **Kernel قابل للتوسع** بدون الحاجة لإعادة التصميم لاحقاً.

---

## 1️⃣ HTTP Kernel

تم بناء:

```

Slim AppFactory
ContainerFactory
Settings Loader
RoutesProvider

```

---

## 2️⃣ Dependency Injection

تم توصيل الخدمات عبر:

```

PHP-DI Container

```

الخدمات الأساسية:

```

Settings
ResponseFactory
ErrorSerializer
TrustedNetworkMatcher

```

---

## 3️⃣ Error Handling

تم اعتماد:

```

maatify/exceptions

```

لإخراج الأخطاء وفق:

```

RFC7807 Problem Details

```

Middleware:

```

IamExceptionMiddleware

```

---

## 4️⃣ Request Tracing

تم بناء:

```

RequestIdMiddleware

```

السياسة:

```

internal request_id → generated always
external request_id → accepted only if valid UUID

```

---

## 5️⃣ Internal Security Gate

تم بناء:

```

TrustedNetworkMatcher
TrustedNetworkMiddleware

```

يدعم:

```

IPv4
IPv6
CIDR
exact IP

```

ويتم تطبيقه على:

```

/internal/*

```

---

## 6️⃣ Base Routes

تم تنفيذ:

```

GET /health
GET /version

```

---

## 7️⃣ Domain Foundation

تم تنفيذ:

```

IdentifierTypeEnum
EmailCanonicalizer
PhoneCanonicalizer
IdentifierCanonicalizer
LookupSecretProviderInterface
LookupHmac

```

الهدف:

```

deterministic blind index lookup

```

---

# 📍 Current Phase

النظام الآن في:

```

Phase A Complete
Ready for Phase B

```

---

# 🏗 Phase B — Identity Provisioning

## الهدف

تمكين النظام من **إنشاء الهويات داخل IAM**.

---

## Endpoint

```

POST /internal/actors/provision

```

---

## المسؤوليات

```

create actor
create identifier
encrypt identifier
generate blind index
create credential

```

---

## Flow

```

Request
│
▼
validate input
│
▼
canonicalize identifier
│
▼
generate lookup_hash
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

# 🔐 Phase C — Authentication Engine

بعد provisioning.

---

## Login Endpoint

```

POST /auth/login

```

---

## Flow

```

identifier lookup
verify credential
create session
issue tokens

```

---

## Components

```

AuthenticationService
CredentialService
SessionService
TokenService

```

---

# 🔑 Phase D — Token Infrastructure

---

## Access Token

```

JWT

```

Algorithm:

```

Ed25519

```

---

## Endpoint

```

GET /.well-known/jwks.json

```

---

## Token Claims

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

# 🔄 Phase E — Session Lifecycle

---

## Session Creation

```

create iam_sessions
generate refresh token
store refresh_token_hash

```

---

## Refresh Flow

```

POST /auth/refresh

```

Behavior:

```

refresh rotation
grace window

```

---

## Logout

```

POST /auth/logout

```

---

# 🛡 Phase F — Abuse Protection

---

## Rate Limiting

limits applied to:

```

IP
identifier
client
tenant

```

---

## Protection

```

brute force
credential stuffing
identifier enumeration

```

---

# ✉️ Phase G — Verification Flows

---

## Email Verification

```

send verification link
update identifier.is_verified

```

---

## Phone Verification

```

OTP
SMS verification

```

---

# 🔐 Phase H — MFA

إضافة:

```

TOTP
device trust
risk-based auth

```

---

# 📊 Phase I — Monitoring & Audit

---

## Security Events

```

login success
login failure
session created
session revoked
token refresh
rate limit triggered

```

---

## Audit Log

يجب تسجيل:

```

timestamp
actor_id
client_id
ip
event

```

---

# 🚀 Phase J — Production Hardening

---

## Infrastructure

```

mTLS for internal APIs
private network routing
secure key store
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

# 🧠 Final Target Architecture

```

Applications
│
├── Admin Panel
├── Mobile Apps
├── Website
└── Internal Services
│
▼
IAM API
│
▼
Domain Core
│
▼
Infrastructure

```

---

# 📌 Production Definition

النظام يعتبر **Production Ready** عندما يتم تنفيذ:

```

Phase A → Phase J

```

ويشمل:

```

identity provisioning
authentication
session lifecycle
token infrastructure
abuse protection
verification flows
monitoring
security hardening

```

---

# End of Document
```

---

# 📌 ملاحظة مهمة

هذا الملف **ليس توثيق فقط**.

بل هو:

```
Execution Source of Truth
```

أي أن:

* كل مرحلة يتم تنفيذها
* يتم تحديث الحالة داخل هذا الملف

مثال:

```
Phase B — IN PROGRESS
Phase C — PENDING
```

---
