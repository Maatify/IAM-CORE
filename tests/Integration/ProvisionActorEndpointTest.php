<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\TestDatabaseManager;

final class ProvisionActorEndpointTest extends HttpTestCase
{
    public function test_provision_actor_endpoint_creates_actor(): void
    {
        $pdo = TestDatabaseManager::connection();

        $pdo->exec("
            INSERT INTO iam_tenants (`key`, name, status, metadata_json, primary_identifier_type)
            VALUES ('t1','Tenant 1','ACTIVE',NULL,'EMAIL')
        ");

        $tenantId = (int) $pdo->lastInsertId();

        $this->postJson('/internal/actors', [
            'tenant_id' => $tenantId,
            'actor_type' => 'CUSTOMER',
            'identifier_type' => 'EMAIL',
            'identifier' => 'user@example.com',
            'password' => 'password',
            'customer_mode' => 'standard',
            'metadata' => []
        ])
            ->assertCreated()
            ->assertJsonStructure(['actor_id']);
    }
}
