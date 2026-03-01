# MAATIFY IAM CORE

## IAM_V1_FINAL_LOCKED_SPEC.md

**Status:** Locked v1 Baseline (Hardened)
**Mode:** Production-Ready Minimal IdP
**Security Posture:** Secure-by-Design, Purpose-Isolated

---

# 1. Overview

This document defines the hardened and locked execution baseline for IAM v1.

IAM is an Identity Provider (IdP) built for:

* Multi-tenant environments
* Multi-client applications
* Multi-actor segregation
* Cryptographic isolation
* Long-term architectural stability

IAM is NOT a business permission engine.

---

# 2. Architectural Non-Negotiable Rules

## 2.1 IAM Does Not Know Business Data

IAM manages:

* Identity
* Authentication
* Sessions
* Tokens
* Tenant & client isolation
* Actor type segregation

IAM does NOT manage:

* Orders
* Wallets
* Products
* Domain permissions
* Business RBAC

---

## 2.2 Actor Type Is a Hard Gate

Login requires:

* tenant_key
* client_key
* actor_type

All tokens MUST include `actor_type`.

Business services MUST validate:

* tenant_id
* client_id
* actor_type

Customer ≠ Distributor even if same email.

---

## 2.3 Tenant & Client Validation Order (MANDATORY)

The system MUST:

1. Validate tenant existence.
2. Validate client existence.
3. Validate client belongs to tenant.
4. Only then perform actor lookup.

Actor lookup MUST NOT happen before tenant/client validation.

---

## 2.4 Key Purpose Isolation (Container-Level Only)

Crypto module remains untouched.

Each purpose MUST have its own key set:

| Purpose     | Usage                             |
|-------------|-----------------------------------|
| PII_ENC     | AES-GCM encryption of identifiers |
| JWT_SIGN    | Asymmetric JWT signing            |
| MFA_TOTP    | TOTP seed encryption              |
| ABUSE_HMAC  | Abuse/risk HMAC                   |
| LOOKUP_HMAC | Blind index hashing               |

🚫 Root keys MUST NEVER be reused across purposes.
🚫 No generic KeyRotationService binding allowed.

---

# 3. Canonicalization Contract (MANDATORY)

Before computing lookup hash:

### Email

```php
email_canonical = strtolower(trim(email));
```

### Phone (v1 Minimal Normalization)

* Remove spaces
* Remove dashes
* Keep digits
* Preserve leading "+"

Canonicalization MUST be deterministic.

---

# 4. Blind Index Design

## 4.1 Purpose

Enable lookup without decrypting PII.

## 4.2 Algorithm

```php
lookup_hash = HMAC_SHA256(canonical_identifier, LOOKUP_SECRET);
```

* Stored as binary(32)
* Deterministic
* Non-reversible
* Uses LOOKUP_HMAC key set

🚫 MUST NOT reuse PII_ENC keys
🚫 MUST NOT use unsalted hash

## 4.3 Logging Rule

Lookup hashes MUST NOT be logged.

---

# 5. Password Hashing

* Argon2id
* Pepper stored outside DB (env/secret store)
* Pepper MUST NOT be stored in database
* Optional future support for pepper_version

---

# 6. Data Model (v1 Hardened)

## 6.1 tenants

* id
* key (unique)
* name
* status
* metadata_json
* timestamps

---

## 6.2 clients

* id
* tenant_id
* client_key
* type
* status
* timestamps

---

## 6.3 actors

* id
* tenant_id
* actor_type
* status
* password_hash

Encrypted fields:

* email_cipher
* email_iv
* email_tag
* email_key_id
* email_algorithm

Blind index fields:

* email_lookup_hash (binary 32)
* phone_lookup_hash (binary 32)

Unique constraints:

* (tenant_id, actor_type, email_lookup_hash)
* (tenant_id, actor_type, phone_lookup_hash)

---

## 6.4 sessions

* id (uuid)
* actor_id
* client_id
* refresh_token_hash (unique)
* prev_refresh_token_hash
* prev_refresh_expires_at
* device_id
* ip
* user_agent
* last_seen_at
* expires_at
* revoked_at
* timestamps

Indexes:

* refresh_token_hash
* actor_id + revoked_at
* expires_at

---

# 7. Login Security Contract

## 7.1 Uniform Failure Response (MANDATORY)

Login failures MUST:

* Return identical HTTP status
* Return identical body
* Avoid timing differences

If actor not found:

* System MUST run password verification on dummy Argon2id hash.

This prevents account enumeration.

---

## 7.2 Rate Limiting (MANDATORY in v1)

The system MUST implement rate limiting on:

* POST /auth/login
* POST /auth/refresh

Minimum requirements:

* Per IP bucket
* Per tenant+identifier bucket
* Exponential backoff recommended

---

# 8. Token Architecture

## 8.1 Access JWT

* TTL: 5–15 minutes
* Asymmetric signing (Ed25519 recommended)
* kid MUST be included
* Stateless

Required claims:

```json
{
  "iss": "maatify-iam",
  "sub": "actor_id",
  "tenant_id": "tenant_key",
  "client_id": "client_key",
  "actor_type": "customer",
  "scopes": ["basic"],
  "iat": 1700000000,
  "exp": 1700000600,
  "jti": "uuid"
}
```

JWT MUST NOT contain:

* PII
* Business roles
* Domain permissions

---

## 8.2 JWT Verification Contract (MANDATORY)

Services verifying JWT MUST:

* Validate signature
* Validate iss
* Validate aud
* Validate exp
* Validate kid
* Enforce algorithm allowlist

Unknown kid MUST be rejected.

Algorithm downgrade MUST be rejected.

---

## 8.3 JWT Key Lifecycle (LOCKED)

Signing key states:

* ACTIVE → used for signing
* VERIFY_ONLY → used for verification only
* RETIRED → removed from JWKS

JWKS MUST expose:

* ACTIVE
* VERIFY_ONLY

Private keys MUST NEVER be exposed.

---

# 9. Refresh Token & Rotation

## 9.1 Rules

* Refresh tokens random (>= 32 bytes)
* Stored hashed
* Rotated on every use
* Bound to device_id

## 9.2 Grace Window (10 Seconds)

Previous refresh token accepted ONLY if:

* Within 10 seconds
* Same session
* Same device_id
* Hash matches prev_refresh_token_hash

After window expires:

* Previous token invalid

Rotation MUST be atomic.

---

# 10. Logout

## 10.1 Single Session Logout

Revoke specific session.

## 10.2 Global Logout (MANDATORY)

Endpoint:

```
POST /auth/logout-all
```

Must revoke all active sessions for actor_id.

---

# 11. JWKS Endpoint

```
GET /.well-known/jwks.json
```

Must:

* Expose public keys only
* Set Cache-Control header
* Allow minimum 5-minute cache

---

# 12. Observability & Logging Rules

Allowed to log:

* login.success
* login.fail
* refresh.success
* refresh.fail
* revoke.single
* revoke.all
* key.rotation
* rate.limit.hit

Forbidden to log:

* Ciphertext
* IV
* Tags
* Raw refresh token
* Lookup hashes
* Private keys

---

# 13. Internal Privileged API

Must be accessible only via:

* Tailscale ACL OR
* mTLS with identity verification

User JWT MUST NOT be accepted for internal API.

Separate authentication required.

---

# 14. Failure Mode Policy

Crypto failure → fail-closed
Key missing → fail-closed
Verification failure → deny

Security over availability.

---

# 15. Security Posture Statement

IAM v1 is:

* Purpose-isolated
* Multi-tenant ready
* Enumeration-resistant
* Replay-resistant
* Cryptographically separated
* Architecturally decoupled
* Minimal yet production-hardened

---

# Final Status

IAM v1 is:

* Threat-modeled
* Red-team reviewed
* Deadlock-checked
* Production-hardened
* Implementation-ready

---
