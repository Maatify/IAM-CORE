<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Controllers;

use Maatify\Iam\Bootstrap\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class VersionController
{
    public function __construct(
        private Settings $settings
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $response->getBody()->write(
            json_encode(
                [
                    'name'        => $this->settings->appName,
                    'version'     => $this->settings->version,
                    'environment' => $this->settings->appEnv,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }
}
