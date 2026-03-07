<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 21:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Requests;

use Maatify\Iam\Domain\DTO\ProvisionActorDTO;
use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;

final readonly class ProvisionActorRequest
{
    public function __construct(
        public int $tenantId,
        public string $actorType,
        public IdentifierTypeEnum $identifierType,
        public string $identifier,
        public string $password,
        public string $customerMode,
        /** @var array<string,mixed> */
        public array $metadata
    ) {
    }

    /**
     * @param   array{
     *     tenant_id:int,
     *     actor_type:string,
     *     identifier_type:string,
     *     identifier:string,
     *     password:string,
     *     customer_mode?:string,
     *     metadata?:array<string,mixed>
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            tenantId      : $payload['tenant_id'],
            actorType     : $payload['actor_type'],
            identifierType: IdentifierTypeEnum::from($payload['identifier_type']),
            identifier    : $payload['identifier'],
            password      : $payload['password'],
            customerMode  : $payload['customer_mode'] ?? 'standard',
            metadata      : $payload['metadata'] ?? []
        );
    }

    public function toCommand(): ProvisionActorDTO
    {
        return new ProvisionActorDTO(
            actorType     : $this->actorType,
            identifierType: $this->identifierType,
            identifier    : $this->identifier,
            password      : $this->password,
            customerMode  : $this->customerMode,
            metadata      : $this->metadata
        );
    }
}
