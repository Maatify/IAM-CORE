-- MAATIFY IAM v1
-- Engine: MySQL 5.7+ (SAFE BASELINE)
-- Status: LOCKED BASELINE
-- Notes:
--  - No CHECK constraints (MySQL 8 feature)
--  - No generated columns / functional indexes
--  - Multi-tenant isolation enforced at DB level (composite FKs)

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 1) Tenants
-- ------------------------------------------------------------
CREATE TABLE iam_tenants (
                             id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                             tenant_key    VARCHAR(64)      NOT NULL,   -- public stable key (e.g. "project_alpha")
                             name          VARCHAR(190)     NOT NULL,
                             status        VARCHAR(32)      NOT NULL DEFAULT 'ACTIVE', -- ACTIVE|SUSPENDED|DELETED
                             metadata_json JSON            NULL,
                             created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                             updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                             PRIMARY KEY (id),
                             UNIQUE KEY uq_iam_tenants_tenant_key (tenant_key),
                             KEY idx_iam_tenants_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2) Clients (per tenant)
-- ------------------------------------------------------------
CREATE TABLE iam_clients (
                             id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                             tenant_id     BIGINT UNSIGNED NOT NULL,
                             client_key    VARCHAR(64)      NOT NULL,   -- e.g. "web_bff", "mobile_android"
                             client_type   VARCHAR(32)      NOT NULL,   -- WEB|MOBILE|SERVICE
                             status        VARCHAR(32)      NOT NULL DEFAULT 'ACTIVE',
                             metadata_json JSON            NULL,
                             created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                             updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                             PRIMARY KEY (id),

                             CONSTRAINT fk_iam_clients_tenant
                                 FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                     ON DELETE RESTRICT ON UPDATE CASCADE,

                             UNIQUE KEY uq_iam_clients_tenant_client_key (tenant_id, client_key),
                             KEY idx_iam_clients_tenant_status (tenant_id, status),
                             KEY idx_iam_clients_type (client_type),

    -- Composite FK target for sessions (prevents cross-tenant mixing)
                             UNIQUE KEY uq_iam_clients_id_tenant (id, tenant_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3) Actors (identity only; NO business data)
-- ------------------------------------------------------------
CREATE TABLE iam_actors (
                            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                            tenant_id      BIGINT UNSIGNED NOT NULL,
                            actor_type     VARCHAR(32)      NOT NULL, -- customer|distributor|...
                            status         VARCHAR(32)      NOT NULL DEFAULT 'ACTIVE', -- ACTIVE|SUSPENDED|DELETED
                            password_hash  VARCHAR(255)     NOT NULL, -- Argon2id output

    -- Encrypted PII: Email (AES-GCM)
                            email_cipher     BLOB           NULL,
                            email_iv         VARBINARY(32)  NULL,
                            email_tag        VARBINARY(32)  NULL,
                            email_key_id     VARCHAR(64)    NULL,
                            email_algorithm  VARCHAR(32)    NULL, -- e.g. "aes-256-gcm"

    -- Encrypted PII: Phone (AES-GCM)
                            phone_cipher     BLOB           NULL,
                            phone_iv         VARBINARY(32)  NULL,
                            phone_tag        VARBINARY(32)  NULL,
                            phone_key_id     VARCHAR(64)    NULL,
                            phone_algorithm  VARCHAR(32)    NULL,

    -- Blind Index (HMAC-SHA256) - stored as 32 bytes
                            email_lookup_hash VARBINARY(32) NULL,
                            phone_lookup_hash VARBINARY(32) NULL,

                            last_activity_at DATETIME       NULL,

                            created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                            PRIMARY KEY (id),

                            CONSTRAINT fk_iam_actors_tenant
                                FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                    ON DELETE RESTRICT ON UPDATE CASCADE,

    -- Prevent cross-tenant & cross-actor-type identity collision
                            UNIQUE KEY uq_iam_actors_email_lookup (tenant_id, actor_type, email_lookup_hash),
                            UNIQUE KEY uq_iam_actors_phone_lookup (tenant_id, actor_type, phone_lookup_hash),

                            KEY idx_iam_actors_tenant_type_status (tenant_id, actor_type, status),
                            KEY idx_iam_actors_last_activity (last_activity_at),

    -- Composite FK target for sessions (prevents cross-tenant mixing)
                            UNIQUE KEY uq_iam_actors_id_tenant (id, tenant_id)
) ENGINE=InnoDB;

-- Note:
-- MySQL allows multiple NULLs in UNIQUE indexes.
-- This is OK: email_lookup_hash/phone_lookup_hash can be NULL until provided.

-- ------------------------------------------------------------
-- 4) Sessions (stateful refresh; revocation authority)
-- ------------------------------------------------------------
CREATE TABLE iam_sessions (
                              id            CHAR(36)         NOT NULL,  -- UUID string (portable)
                              tenant_id     BIGINT UNSIGNED  NOT NULL,
                              actor_id      BIGINT UNSIGNED  NOT NULL,
                              client_id     BIGINT UNSIGNED  NOT NULL,

    -- Refresh token hashes (SHA-256 raw bytes -> 32 bytes)
                              refresh_token_hash       VARBINARY(32) NOT NULL,
                              prev_refresh_token_hash  VARBINARY(32) NULL,
                              prev_refresh_expires_at  DATETIME      NULL,

                              device_id     VARCHAR(128)     NOT NULL, -- binding metadata (NOT trust factor)
                              ip            VARBINARY(16)    NULL,     -- IPv4/IPv6 packed
                              user_agent    VARCHAR(255)     NULL,

                              last_seen_at  DATETIME         NULL,
                              expires_at    DATETIME         NOT NULL,
                              revoked_at    DATETIME         NULL,

                              created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                              PRIMARY KEY (id),

    -- Tenant FK
                              CONSTRAINT fk_iam_sessions_tenant
                                  FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                      ON DELETE RESTRICT ON UPDATE CASCADE,

    -- Composite FKs to prevent cross-tenant mixing
                              CONSTRAINT fk_iam_sessions_actor_tenant
                                  FOREIGN KEY (actor_id, tenant_id) REFERENCES iam_actors(id, tenant_id)
                                      ON DELETE RESTRICT ON UPDATE CASCADE,

                              CONSTRAINT fk_iam_sessions_client_tenant
                                  FOREIGN KEY (client_id, tenant_id) REFERENCES iam_clients(id, tenant_id)
                                      ON DELETE RESTRICT ON UPDATE CASCADE,

    -- Token uniqueness prevents duplicates/replay collisions at storage layer
                              UNIQUE KEY uq_iam_sessions_refresh_hash (refresh_token_hash),

                              KEY idx_iam_sessions_actor_active (actor_id, revoked_at),
                              KEY idx_iam_sessions_tenant_actor (tenant_id, actor_id),
                              KEY idx_iam_sessions_client (client_id),
                              KEY idx_iam_sessions_expires (expires_at),
                              KEY idx_iam_sessions_last_seen (last_seen_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5) JWT Signing Keys (Public Metadata Only; private key NOT stored here)
-- Optional but recommended for lifecycle & JWKS management.
-- ------------------------------------------------------------
CREATE TABLE iam_jwt_signing_keys (
                                      id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                      tenant_id    BIGINT UNSIGNED NOT NULL,

                                      kid          VARCHAR(64)      NOT NULL, -- key id exposed in JWT header & JWKS
                                      status       VARCHAR(32)      NOT NULL, -- ACTIVE|VERIFY_ONLY|RETIRED
                                      algorithm    VARCHAR(32)      NOT NULL DEFAULT 'EdDSA', -- EdDSA (Ed25519)

    -- Public key material (safe to store)
                                      public_key   BLOB             NOT NULL,

                                      activated_at DATETIME         NULL,
                                      retired_at   DATETIME         NULL,

                                      created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      updated_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                      PRIMARY KEY (id),

                                      CONSTRAINT fk_iam_jwt_keys_tenant
                                          FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                              ON DELETE RESTRICT ON UPDATE CASCADE,

                                      UNIQUE KEY uq_iam_jwt_keys_tenant_kid (tenant_id, kid),
                                      KEY idx_iam_jwt_keys_tenant_status (tenant_id, status)
) ENGINE=InnoDB;