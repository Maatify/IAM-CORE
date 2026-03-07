<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 01:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap\Container;

use DI\Container;
use Maatify\Iam\Bootstrap\Settings;
use Maatify\Iam\Domain\Repository\ActorCredentialRepositoryInterface;
use Maatify\Iam\Domain\Repository\ActorIdentifierRepositoryInterface;
use Maatify\Iam\Domain\Repository\ActorRepositoryInterface;
use Maatify\Iam\Infrastructure\Database\PDOFactory;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorCredentialRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorIdentifierRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorRepository;
use PDO;

class RepositoryContainerConfig implements ContainerModule
{
    public function register(Container $container, Settings $settings): void
    {
        $container->set(PDO::class, function () use ($settings) {
            $factory = new PDOFactory(
                $settings->dbHost,
                $settings->dbName,
                $settings->dbUser,
                $settings->dbPassword
            );
            return $factory->create();
        });

        $container->set(
            PDOFactory::class,
            function () use ($settings) {
                return new PDOFactory(
                    $settings->dbHost,
                    $settings->dbName,
                    $settings->dbUser,
                    $settings->dbPassword
                );
            }
        );

        $container->set(
            ActorRepositoryInterface::class,
            function (Container $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoActorRepository($pdo);
            }
        );

        $container->set(
            ActorIdentifierRepositoryInterface::class,
            function (Container $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoActorIdentifierRepository($pdo);
            }
        );

        $container->set(
            ActorCredentialRepositoryInterface::class,
            function (Container $c) {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoActorCredentialRepository($pdo);
            }
        );
    }
}
