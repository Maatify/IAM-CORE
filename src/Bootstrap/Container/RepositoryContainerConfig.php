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
use Maatify\Iam\Application\Contract\TransactionManagerInterface;
use Maatify\Iam\Bootstrap\Settings;
use Maatify\Iam\Domain\Repository\ActorCredentialRepositoryInterface;
use Maatify\Iam\Domain\Repository\ActorIdentifierRepositoryInterface;
use Maatify\Iam\Domain\Repository\ActorRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientNonceRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientSecretRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientSigningSecretRepositoryInterface;
use Maatify\Iam\Domain\Repository\IdempotencyRepositoryInterface;
use Maatify\Iam\Infrastructure\Database\PDOFactory;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorCredentialRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorIdentifierRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoClientNonceRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoClientRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoClientSecretRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoClientSigningSecretRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoIdempotencyRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoTransactionManager;
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

        $container->set(
            ClientRepositoryInterface::class,
            fn ($c) => new PdoClientRepository(
                $c->get(PDO::class)
            )
        );

        $container->set(
            ClientSecretRepositoryInterface::class,
            fn ($c) => new PdoClientSecretRepository(
                $c->get(PDO::class)
            )
        );

        $container->set(
            ClientNonceRepositoryInterface::class,
            fn ($c) => new PdoClientNonceRepository(
                $c->get(PDO::class)
            )
        );

        $container->set(
            ClientSigningSecretRepositoryInterface::class,
            fn ($c) => new PdoClientSigningSecretRepository(
                $c->get(PDO::class)
            )
        );

        $container->set(
            TransactionManagerInterface::class,
            fn ($c) => $c->get(PdoTransactionManager::class)
        );

        $container->set(
            IdempotencyRepositoryInterface::class,
            fn ($c) => new PdoIdempotencyRepository(
                $c->get(PDO::class)
            )
        );

    }
}
