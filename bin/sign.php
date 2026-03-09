<?php

declare(strict_types=1);

use Maatify\Iam\Infrastructure\IamClient\IamClient;

require __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    echo "Usage:\n";
    echo "php bin/sign.php create-actor [email]\n";
    exit(1);
}

$action = $argv[1];
$email = $argv[2] ?? ('test' . random_int(1000,9999) . '@example.com');

$client = new IamClient(
    'http://localhost:8081',
    'test-client',
    '58266ebc267f2a40ab124a0054262cc26591b3876412c79ac5e650d3cf733dda'
);

try {

    switch ($action) {

        case 'create-actor':

            $data = [
                'actor_type' => 'CUSTOMER',
                'identifier_type' => 'EMAIL',
                'identifier' => $email,
                'password' => 'StrongPass123!'
            ];

            echo "\n========================\n";
            echo "REQUEST\n";
            echo "========================\n";

            echo "ACTION: create-actor\n";
            echo "EMAIL: $email\n\n";

            echo "BODY:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

            $result = $client->createActor($data);

            echo "========================\n";
            echo "RESPONSE\n";
            echo "========================\n";

            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            break;

        default:
            echo "Unknown action\n";
            exit(1);
    }

} catch (Throwable $e) {

    echo "\n========================\n";
    echo "ERROR\n";
    echo "========================\n";

    echo $e->getMessage() . "\n";

    exit(1);
}
