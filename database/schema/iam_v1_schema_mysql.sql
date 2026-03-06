/* ===========================================================
 * MAATIFY IAM v1 — SHARED DB SCHEMA (PREFIXED TABLES)
 * -----------------------------------------------------------
 * - No CREATE DATABASE
 * - No DROP
 * - No global SQL mode changes
 * - Prefix isolation: iam_*
 * =========================================================== */

/* ===========================================================
 * 1) tenants
 * =========================================================== */
CREATE TABLE IF NOT EXISTS iam_tenants (
                                           id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                           `key` VARCHAR(64) NOT NULL,
                                           name VARCHAR(128) NOT NULL,
                                           status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',
                                           metadata_json JSON NULL,
                                           primary_identifier_type VARCHAR(16) NOT NULL,
                                           created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                           PRIMARY KEY (id),
                                           UNIQUE KEY uq_iam_tenants_key (`key`),
                                           CONSTRAINT chk_iam_tenants_status
                                               CHECK (status IN ('ACTIVE', 'SUSPENDED')),
                                           CONSTRAINT chk_iam_tenants_primary_identifier_type
                                               CHECK (primary_identifier_type IN ('EMAIL', 'PHONE'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ===========================================================
 * 2) clients
 * =========================================================== */
CREATE TABLE IF NOT EXISTS iam_clients (
                                           id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                           tenant_id BIGINT UNSIGNED NOT NULL,
                                           client_key VARCHAR(64) NOT NULL,
                                           type VARCHAR(32) NOT NULL DEFAULT 'PUBLIC',
                                           status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',
                                           created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                           PRIMARY KEY (id),

                                           UNIQUE KEY uq_iam_clients_tenant_client_key (tenant_id, client_key),

                                           KEY idx_iam_clients_tenant_id (tenant_id),

                                           CONSTRAINT fk_iam_clients_tenant
                                               FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                                   ON UPDATE RESTRICT ON DELETE RESTRICT,

                                           CONSTRAINT chk_iam_clients_status
                                               CHECK (status IN ('ACTIVE', 'SUSPENDED')),

                                           CONSTRAINT chk_iam_clients_type
                                               CHECK (type IN ('PUBLIC', 'CONFIDENTIAL', 'INTERNAL'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ===========================================================
 * 3) actors
 * =========================================================== */
CREATE TABLE IF NOT EXISTS iam_actors (
                                          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                          tenant_id BIGINT UNSIGNED NOT NULL,

                                          actor_type VARCHAR(32) NOT NULL,

    /* Optional business classification */
                                          customer_mode VARCHAR(32) NULL,

                                          status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',

    /* Extensible attributes */
                                          metadata_json JSON NULL,

                                          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                          PRIMARY KEY (id),

                                          KEY idx_iam_actors_tenant_type (tenant_id, actor_type),
                                          KEY idx_iam_actors_tenant_status (tenant_id, status),

                                          CONSTRAINT fk_iam_actors_tenant
                                              FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                                  ON UPDATE RESTRICT ON DELETE RESTRICT,

                                          CONSTRAINT chk_iam_actors_status
                                              CHECK (status IN ('ACTIVE', 'SUSPENDED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ===========================================================
 * 4) actor_identifiers
 *
 * - Identifiers are NOT in actors
 * - Blind index lookup_hash is deterministic HMAC (BINARY 32)
 * - Unique:
 *   (actor_id, identifier_type)
 *   (tenant_id, actor_type, identifier_type, lookup_hash)
 * =========================================================== */
CREATE TABLE IF NOT EXISTS iam_actor_identifiers (
                                                     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                                                     actor_id BIGINT UNSIGNED NOT NULL,
                                                     tenant_id BIGINT UNSIGNED NOT NULL,
                                                     actor_type VARCHAR(32) NOT NULL,

                                                     identifier_type VARCHAR(16) NOT NULL,
                                                     lookup_hash BINARY(32) NOT NULL,

                                                     cipher VARBINARY(512) NOT NULL,
                                                     iv VARBINARY(32) NOT NULL,
                                                     tag VARBINARY(32) NOT NULL,
                                                     key_id VARCHAR(64) NOT NULL,
                                                     algorithm VARCHAR(32) NOT NULL DEFAULT 'AES-256-GCM',

                                                     is_verified TINYINT(1) NOT NULL DEFAULT 0,

                                                     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                     updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                                     PRIMARY KEY (id),

                                                     UNIQUE KEY uq_iam_actor_identifiers_actor_type (actor_id, identifier_type),

                                                     UNIQUE KEY uq_iam_actor_identifiers_lookup
                                                         (tenant_id, actor_type, identifier_type, lookup_hash),

                                                     KEY idx_iam_actor_identifiers_actor (actor_id),
                                                     KEY idx_iam_actor_identifiers_lookup_hash (lookup_hash),
                                                     KEY idx_iam_actor_identifiers_tenant_actor (tenant_id, actor_type),

                                                     CONSTRAINT fk_iam_actor_identifiers_actor
                                                         FOREIGN KEY (actor_id) REFERENCES iam_actors(id)
                                                             ON UPDATE RESTRICT ON DELETE RESTRICT,

                                                     CONSTRAINT fk_iam_actor_identifiers_tenant
                                                         FOREIGN KEY (tenant_id) REFERENCES iam_tenants(id)
                                                             ON UPDATE RESTRICT ON DELETE RESTRICT,

                                                     CONSTRAINT chk_iam_actor_identifiers_identifier_type
                                                         CHECK (identifier_type IN ('EMAIL', 'PHONE')),

                                                     CONSTRAINT chk_iam_actor_identifiers_algorithm
                                                         CHECK (algorithm IN ('AES-256-GCM')),

                                                     CONSTRAINT chk_iam_actor_identifiers_is_verified
                                                         CHECK (is_verified IN (0, 1))

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ===========================================================
 * 5) actor_credentials
 *
 * - PASSWORD: secret_hash (Argon2id + Pepper)
 * - OAUTH: provider_subject + provider_lookup_hash
 * - Unique:
 *   (actor_id, credential_type)
 *   (credential_type, provider_lookup_hash)  -- when OAuth
 * =========================================================== */
CREATE TABLE IF NOT EXISTS iam_actor_credentials (
                                                     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                                                     actor_id BIGINT UNSIGNED NOT NULL,

                                                     credential_type VARCHAR(32) NOT NULL,

    /* PASSWORD credential */
                                                     secret_hash VARCHAR(255) NULL,
                                                     pepper_id VARCHAR(32) NULL,

    /* OAuth credential */
                                                     provider_subject VARCHAR(191) NULL,
                                                     provider_lookup_hash BINARY(32) NULL,

                                                     metadata_json JSON NULL,

                                                     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                     updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                                     PRIMARY KEY (id),

                                                     UNIQUE KEY uq_iam_actor_credentials_actor_type
                                                         (actor_id, credential_type),

                                                     UNIQUE KEY uq_iam_actor_credentials_provider
                                                         (credential_type, provider_lookup_hash),

                                                     KEY idx_iam_actor_credentials_actor (actor_id),
                                                     KEY idx_iam_actor_credentials_type (credential_type),

                                                     CONSTRAINT fk_iam_actor_credentials_actor
                                                         FOREIGN KEY (actor_id) REFERENCES iam_actors(id)
                                                             ON UPDATE RESTRICT ON DELETE RESTRICT,

                                                     CONSTRAINT chk_iam_actor_credentials_type
                                                         CHECK (credential_type IN ('PASSWORD', 'OAUTH_GOOGLE', 'OAUTH_MICROSOFT')),

                                                     CONSTRAINT chk_iam_actor_credentials_password_requires_hash
                                                         CHECK (
                                                             (credential_type = 'PASSWORD'
                                                                 AND secret_hash IS NOT NULL
                                                                 AND pepper_id IS NOT NULL)
                                                                 OR
                                                             (credential_type <> 'PASSWORD'
                                                                 AND secret_hash IS NULL
                                                                 AND pepper_id IS NULL)
                                                             ),

                                                     CONSTRAINT chk_iam_actor_credentials_oauth_fields
                                                         CHECK (
                                                             (credential_type IN ('OAUTH_GOOGLE', 'OAUTH_MICROSOFT')
                                                                 AND provider_subject IS NOT NULL
                                                                 AND provider_lookup_hash IS NOT NULL)
                                                                 OR
                                                             (credential_type = 'PASSWORD'
                                                                 AND provider_subject IS NULL
                                                                 AND provider_lookup_hash IS NULL)
                                                             )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ===========================================================
 * 6) sessions
 *
 * id: UUID string (CHAR(36)) - spec says uuid
 * refresh_token_hash: hashed only (BINARY 32) unique
 * rotation: prev_refresh_token_hash + prev_refresh_expires_at
 * revocation: revoked_at
 * device-bound: device_id, ip, user_agent
 * =========================================================== */
CREATE TABLE IF NOT EXISTS iam_sessions (
                                            id CHAR(36) NOT NULL,

                                            actor_id BIGINT UNSIGNED NOT NULL,
                                            client_id BIGINT UNSIGNED NOT NULL,

                                            refresh_token_hash BINARY(32) NOT NULL,
                                            prev_refresh_token_hash BINARY(32) NULL,
                                            prev_refresh_expires_at DATETIME NULL,

                                            device_id VARCHAR(128) NOT NULL,
                                            ip VARBINARY(16) NULL,                 -- packed IPv4/IPv6
                                            user_agent VARCHAR(255) NULL,

                                            last_seen_at DATETIME NULL,
                                            expires_at DATETIME NOT NULL,
                                            revoked_at DATETIME NULL,

                                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                            PRIMARY KEY (id),

                                            UNIQUE KEY uq_iam_sessions_refresh_hash (refresh_token_hash),

                                            KEY idx_iam_sessions_actor (actor_id),
                                            KEY idx_iam_sessions_client (client_id),
                                            KEY idx_iam_sessions_actor_revoked (actor_id, revoked_at),
                                            KEY idx_iam_sessions_expires_at (expires_at),
                                            KEY idx_iam_sessions_device (device_id),

                                            CONSTRAINT fk_iam_sessions_actor
                                                FOREIGN KEY (actor_id) REFERENCES iam_actors(id)
                                                    ON UPDATE RESTRICT ON DELETE RESTRICT,

                                            CONSTRAINT fk_iam_sessions_client
                                                FOREIGN KEY (client_id) REFERENCES iam_clients(id)
                                                    ON UPDATE RESTRICT ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;