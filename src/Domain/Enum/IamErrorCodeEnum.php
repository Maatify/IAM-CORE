<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum IamErrorCodeEnum: string implements ErrorCodeInterface
{
    // ================================
    // AUTHENTICATION (401)
    // ================================
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case INVALID_TOKEN = 'INVALID_TOKEN';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    case REFRESH_TOKEN_REUSED = 'REFRESH_TOKEN_REUSED';

    // ================================
    // AUTHORIZATION / SECURITY (403)
    // ================================
    case ACTOR_SUSPENDED = 'ACTOR_SUSPENDED';
    case TENANT_SUSPENDED = 'TENANT_SUSPENDED';
    case CLIENT_NOT_ALLOWED = 'CLIENT_NOT_ALLOWED';

    // ================================
    // NOT FOUND (404)
    // ================================
    case ACTOR_NOT_FOUND = 'ACTOR_NOT_FOUND';
    case TENANT_NOT_FOUND = 'TENANT_NOT_FOUND';
    case SESSION_NOT_FOUND = 'SESSION_NOT_FOUND';

    // ================================
    // CONFLICT (409)
    // ================================
    case ACTOR_ALREADY_EXISTS = 'ACTOR_ALREADY_EXISTS';
    case SESSION_ALREADY_REVOKED = 'SESSION_ALREADY_REVOKED';

    // ================================
    // SYSTEM (500)
    // ================================
    case TOKEN_SIGNING_FAILED = 'TOKEN_SIGNING_FAILED';
    case CRYPTO_FAILURE = 'CRYPTO_FAILURE';
    case SESSION_PERSISTENCE_FAILED = 'SESSION_PERSISTENCE_FAILED';
    case DATABASE_WRITE_FAILED = 'DATABASE_WRITE_FAILED';

    public function getValue(): string
    {
        return $this->value;
    }
}
