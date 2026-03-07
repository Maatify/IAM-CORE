<?php

declare(strict_types=1);

namespace Tests\Support;

use Slim\Factory\AppFactory;
use Slim\App;
use PDO;

use Maatify\Iam\Presentation\Http\Controllers\ProvisionActorController;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Validator\RespectValidator;
use Maatify\Iam\Presentation\Http\Response\JsonResponseFactory;

final class TestAppFactory
{
    /**
     * @return App<\Psr\Container\ContainerInterface|null>
     */
    public static function create(PDO $pdo): App
    {
        $app = AppFactory::create();

        $app->addBodyParsingMiddleware();

        $service = TestServiceFactory::provisionActorService($pdo);

        $validationGuard = new ValidationGuard(
            new RespectValidator()
        );

        $json = new JsonResponseFactory();

        $controller = new ProvisionActorController(
            $service,
            $validationGuard,
            $json
        );

        $app->post('/internal/actors', $controller);

        return $app;
    }
}
