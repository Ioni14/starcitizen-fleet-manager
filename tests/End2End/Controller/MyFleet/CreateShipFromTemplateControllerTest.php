<?php

namespace App\Tests\End2End\Controller\MyFleet;

use App\Domain\Event\UpdatedFleetShipEvent;
use App\Tests\End2End\WebTestCase;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

class CreateShipFromTemplateControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_should_create_a_ship_based_on_ship_template(): void
    {
        static::$connection->executeStatement(<<<SQL
                INSERT INTO users(id, roles, auth0_username, created_at)
                VALUES ('00000000-0000-0000-0000-000000000001', '["ROLE_USER"]', 'Ioni', '2021-01-01T10:00:00Z');
                INSERT INTO ship_templates(id, author_id, model, image_url, updated_at, version, chassis_name, manufacturer_name, manufacturer_code, ship_size_size, ship_role_role, cargo_capacity_capacity, crew_min, crew_max, price_pledge, price_ingame)
                VALUES ('00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000001', 'Avenger Titan', 'https://example.org/avenger.jpg', '2021-01-01T10:00:00Z', 1, 'Avenger', 'Robert Space Industries', 'RSI', 'small', 'Combat', 11, 1, 3, 5000, 2000000);
            SQL
        );

        static::$client->xmlHttpRequest('POST', '/api/my-fleet/create-ship-from-template', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.static::generateToken('Ioni'),
        ], json_encode([
            'templateId' => '00000000-0000-0000-0000-000000000010',
            'quantity' => 3,
        ]));

        static::assertSame(204, static::$client->getResponse()->getStatusCode());

        $result = static::$connection->executeQuery(<<<SQL
                SELECT * FROM ships WHERE fleet_id = '00000000-0000-0000-0000-000000000001';
            SQL
        )->fetchAssociative();
        static::assertNotFalse($result, 'The ship should be created.');
        static::assertArraySubset([
            'model' => 'Avenger Titan',
            'image_url' => 'https://example.org/avenger.jpg',
            'quantity' => 3,
        ], $result);

        $result = static::$connection->executeQuery(<<<SQL
                SELECT * FROM messenger_messages;
            SQL
        )->fetchAllAssociative();
        static::assertArraySubset([
            [
                'queue_name' => 'organizations_events',
                'body' => '{"ownerId":"00000000-0000-0000-0000-000000000001","model":"Avenger Titan","logoUrl":"https:\/\/example.org\/avenger.jpg","quantity":3}',
                'headers' => json_encode([
                    'type' => UpdatedFleetShipEvent::class,
                    'X-Message-Stamp-'.BusNameStamp::class => '[{"busName":"event.bus"}]',
                    'Content-Type' => 'application/json',
                ]),
            ],
        ], $result);
    }
}
