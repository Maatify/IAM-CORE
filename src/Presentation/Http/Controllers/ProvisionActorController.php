<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 05:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Controllers;

use Maatify\Iam\Application\Service\ProvisionActorService;
use Maatify\Iam\Presentation\Http\Requests\ProvisionActorRequest;
use Maatify\Iam\Presentation\Http\Response\JsonResponseFactory;
use Maatify\Iam\Presentation\Http\Security\RequestContext;
use Maatify\Iam\Schemas\ProvisionActorSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProvisionActorController
{
    public function __construct(
        private ProvisionActorService $service,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {

        /** @var array<string,mixed> $payload */
        $payload = (array) $request->getParsedBody();

        $context = $request->getAttribute(RequestContext::class);

        if (!$context instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        $payload['tenant_id'] = $context->tenantId;

        /*
        |--------------------------------------------------
        | Validate
        |--------------------------------------------------
        */

        $this->validationGuard->check(
            new ProvisionActorSchema(),
            $payload
        );

        /*
        |--------------------------------------------------
        | Build Request DTO
        |--------------------------------------------------
        */

        /**
         * @var array{
         *     tenant_id:int,
         *     actor_type:string,
         *     identifier_type:string,
         *     identifier:string,
         *     password:string,
         *     customer_mode?:string,
         *     metadata?:array<string,mixed>
         * } $payload
         */
        $req = ProvisionActorRequest::fromPayload($payload);

        /*
        |--------------------------------------------------
        | Execute
        |--------------------------------------------------
        */

        $actorId = $this->service->execute(
            $req->tenantId,
            $req->toCommand()
        );

        /*
        |--------------------------------------------------
        | Response
        |--------------------------------------------------
        */

        return $this->json->data($response, ['actor_id' => $actorId], 201);

    }
}
