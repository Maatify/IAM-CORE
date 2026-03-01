# 🏗 MAATIFY IAM — 14 Day Production-Grade Execution Plan

**Scope:** Hardened v1 IdP
**Mode:** Secure-by-Design + Production-Ready
**No UI – API Only**

---

# 📅 WEEK 1 — Core Engine (Correct & Stable)

---

## 🟢 Day 1 — Foundation & Schema Lock

### 🎯 الهدف

قفل البنية الأساسية قبل أي كود business.

### Tasks:

* [ ] Final DB schema migration (tenants, clients, actors, sessions)
* [ ] Add strict constraints & indexes
* [ ] Add composite unique keys
* [ ] Define canonicalization utilities
* [ ] Define LookupHMAC service
* [ ] Define Crypto purpose bindings in container

### Deliverable:

* Schema migration committed
* Crypto container isolated per purpose
* No business code yet

---

## 🟢 Day 2 — Actor Creation Flow

### 🎯 الهدف

Create identity safely.

### Tasks:

* [ ] Canonicalize email/phone
* [ ] Generate lookup_hash
* [ ] Encrypt PII (AES-GCM)
* [ ] Hash password (Argon2id + Pepper)
* [ ] Store actor with strict tenant binding
* [ ] Enforce unique (tenant + actor_type + lookup)

### Hard Rule:

No plaintext PII anywhere.

### Deliverable:

* POST /actors (internal only)
* Tested via CLI or Postman

---

## 🟢 Day 3 — Login Flow (Enumeration Safe)

### 🎯 الهدف

Secure login without leakage.

### Tasks:

* [ ] Validate tenant + client first
* [ ] Canonicalize identifier
* [ ] Compute lookup hash
* [ ] Fetch actor
* [ ] If not found → run dummy Argon verify
* [ ] Verify password
* [ ] Uniform failure response
* [ ] Implement minimal rate limit (per IP)

### Deliverable:

* POST /auth/login
* Enumeration-safe
* Structured logs

---

## 🟢 Day 4 — JWT Engine (Asymmetric + JWKS)

### 🎯 الهدف

Stateless access token properly signed.

### Tasks:

* [ ] Integrate Ed25519 signing
* [ ] Implement kid
* [ ] Create JWKS endpoint
* [ ] Add JWT validation middleware
* [ ] Enforce algorithm allowlist
* [ ] Reject unknown kid

### Deliverable:

* Access JWT fully functional
* GET /.well-known/jwks.json working

---

## 🟢 Day 5 — Refresh Token Rotation (Atomic)

### 🎯 الهدف

Stateful session security.

### Tasks:

* [ ] Generate secure refresh token (>=32 bytes)
* [ ] Hash refresh token before storage
* [ ] Implement rotation on every use
* [ ] Implement prev_refresh_hash
* [ ] Implement 10-second grace window
* [ ] Ensure atomic DB transaction

### Deliverable:

* POST /auth/refresh
* Safe rotation
* Replay-safe

---

## 🟢 Day 6 — Logout & Global Revocation

### 🎯 الهدف

Full control over sessions.

### Tasks:

* [ ] POST /auth/logout (single session)
* [ ] POST /auth/logout-all
* [ ] Mark sessions revoked
* [ ] Prevent refresh on revoked sessions
* [ ] Index optimization for revoke-all

### Deliverable:

* Full revocation working

---

## 🟢 Day 7 — Internal Privileged API Guard

### 🎯 الهدف

Secure internal endpoints.

### Tasks:

* [ ] Implement internal auth layer (Tailscale OR mTLS)
* [ ] Ensure user JWT cannot access internal API
* [ ] Separate routing group
* [ ] Log privileged actions

### Deliverable:

* Internal API isolated
* External access denied

---

# 📅 WEEK 2 — Hardening & Production Safety

---

## 🔵 Day 8 — Advanced Rate Limiting

### Tasks:

* [ ] Per IP limit
* [ ] Per tenant+identifier limit
* [ ] Exponential backoff
* [ ] Redis integration (if available)
* [ ] Log rate-limit hits

---

## 🔵 Day 9 — Key Lifecycle Management

### Tasks:

* [ ] Implement JWT key states (ACTIVE → VERIFY_ONLY → RETIRED)
* [ ] Add key metadata table if needed
* [ ] Implement signing key rotation test
* [ ] JWKS expose active + verify_only only

---

## 🔵 Day 10 — Abuse Protection Layer

### Tasks:

* [ ] Track failed login attempts
* [ ] Add account temporary lock policy
* [ ] Add IP risk scoring placeholder
* [ ] Add structured abuse logs

---

## 🔵 Day 11 — Timing & Side-Channel Audit

### Tasks:

* [ ] Verify uniform login timing
* [ ] Test lookup collision handling
* [ ] Validate constant-time comparisons
* [ ] Add micro-sleep equalization if needed

---

## 🔵 Day 12 — Observability & Monitoring

### Tasks:

* [ ] Add structured logs (JSON)
* [ ] Add correlation IDs
* [ ] Add key usage logs (without secrets)
* [ ] Add login success/failure metrics
* [ ] Add health endpoint

---

## 🔵 Day 13 — Red-Team Simulation

### Attack Tests:

* [ ] JWT replay attempt
* [ ] Refresh reuse after grace window
* [ ] Cross-tenant token usage
* [ ] Invalid kid injection
* [ ] Algorithm downgrade attempt
* [ ] Blind index brute attempt
* [ ] Device spoof attempt

### Fix anything discovered.

---

## 🔵 Day 14 — Final Production Checklist

### Final Lock:

* [ ] Secrets management validated
* [ ] Environment separation
* [ ] Fail-closed behavior verified
* [ ] Backup plan documented
* [ ] Deployment config hardened
* [ ] Internal API isolation verified
* [ ] Spec aligned with implementation

---

# 🏁 What You Get After 14 Days

You get:

* Production-ready IAM
* Multi-tenant hardened
* Cryptographically isolated
* Enumeration resistant
* Replay resistant
* Revocation safe
* Observability enabled
* Key rotation ready
* Scalable horizontally
* Clean separation from business logic

---

# ⏱ Realistic Outcome

14 focused days =
A real Identity Infrastructure layer.

Not a login feature.
A system.

---
