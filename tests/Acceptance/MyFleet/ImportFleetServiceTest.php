<?php

namespace App\Tests\Acceptance\MyFleet;

use App\Application\MyFleet\ImportFleetService;
use App\Application\MyFleet\Input\ImportFleetShip;
use App\Application\Repository\FleetRepositoryInterface;
use App\Domain\Event\UpdatedFleetEvent;
use App\Domain\Event\UpdatedShip;
use App\Domain\Service\EntityIdGeneratorInterface;
use App\Domain\ShipId;
use App\Domain\UserId;
use App\Entity\Fleet;
use App\Infrastructure\Repository\Fleet\InMemoryFleetRepository;
use App\Infrastructure\Service\InMemoryEntityIdGenerator;
use App\Tests\Acceptance\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class ImportFleetServiceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function it_should_import_ships_from_hangar_transfer_format_file(): void
    {
        $userId = UserId::fromString('00000000-0000-0000-0000-000000000001');

        $fleet = new Fleet($userId, new \DateTimeImmutable('2021-01-01T12:00:00+02:00'));
        $fleet->addShip(ShipId::fromString('00000000-0000-0000-0000-000000000010'), 'Avenger', 'https://example.com/avenger.jpg', 2, new \DateTimeImmutable('2021-01-01T10:00:00Z'));
        $fleet->addShip(ShipId::fromString('00000000-0000-0000-0000-000000000011'), 'Javelin', null, 1, new \DateTimeImmutable('2021-01-02T10:00:00Z'));
        $fleet->getAndClearEvents();

        /** @var InMemoryFleetRepository $fleetRepository */
        $fleetRepository = static::$container->get(FleetRepositoryInterface::class);
        $fleetRepository->setFleets([$fleet]);

        /** @var InMemoryEntityIdGenerator $entityIdGenerator */
        $entityIdGenerator = static::$container->get(EntityIdGeneratorInterface::class);
        $entityIdGenerator->setUid('00000000-0000-0000-0000-000000000012');

        /** @var ImportFleetService $service */
        $service = static::$container->get(ImportFleetService::class);
        $service->handle($userId, [
            new ImportFleetShip('Avenger'),
            new ImportFleetShip('Mercury Star Runner'),
            new ImportFleetShip('Mercury Star Runner'),
        ], onlyMissing: false);

        $fleet = $fleetRepository->getFleetByUser($userId);
        static::assertCount(3, $fleet->getShips());
        static::assertSame(3, $fleet->getShipsByModel('Avenger')['00000000-0000-0000-0000-000000000010']->getQuantity());
        static::assertSame('https://example.com/avenger.jpg', $fleet->getShipsByModel('Avenger')['00000000-0000-0000-0000-000000000010']->getImageUrl());
        static::assertSame(1, $fleet->getShipsByModel('Javelin')['00000000-0000-0000-0000-000000000011']->getQuantity());
        static::assertSame(2, $fleet->getShipsByModel('Mercury Star Runner')['00000000-0000-0000-0000-000000000012']->getQuantity());
        static::assertNull($fleet->getShipsByModel('Mercury Star Runner')['00000000-0000-0000-0000-000000000012']->getImageUrl());

        /** @var InMemoryTransport $transport */
        $transport = static::$container->get('messenger.transport.organizations_sub');
        static::assertCount(1, $transport->getSent());
        /** @var UpdatedFleetEvent $message */
        $message = $transport->getSent()[0]->getMessage();
        static::assertInstanceOf(UpdatedFleetEvent::class, $message);
        static::assertEquals(new UpdatedFleetEvent(
            $userId,
            [
                new UpdatedShip('Avenger', 'https://example.com/avenger.jpg', 3),
                new UpdatedShip('Javelin', null, 1),
                new UpdatedShip('Mercury Star Runner', null, 2),
            ],
            1
        ), $message);
    }

    /**
     * @test
     */
    public function it_should_import_only_missing_ships(): void
    {
        $userId = UserId::fromString('00000000-0000-0000-0000-000000000001');

        $fleet = new Fleet($userId, new \DateTimeImmutable('2021-01-01T12:00:00+02:00'));
        $fleet->addShip(ShipId::fromString('00000000-0000-0000-0000-000000000010'), 'Avenger', 'https://example.com/avenger.jpg', 2, new \DateTimeImmutable('2021-01-01T10:00:00Z'));
        $fleet->getAndClearEvents();

        /** @var InMemoryFleetRepository $fleetRepository */
        $fleetRepository = static::$container->get(FleetRepositoryInterface::class);
        $fleetRepository->setFleets([$fleet]);

        /** @var InMemoryEntityIdGenerator $entityIdGenerator */
        $entityIdGenerator = static::$container->get(EntityIdGeneratorInterface::class);
        $entityIdGenerator->setUid('00000000-0000-0000-0000-000000000011');

        /** @var ImportFleetService $service */
        $service = static::$container->get(ImportFleetService::class);
        $service->handle($userId, [
            new ImportFleetShip('Avenger'),
            new ImportFleetShip('Mercury Star Runner'),
            new ImportFleetShip('Mercury Star Runner'),
        ], onlyMissing: true);

        $fleet = $fleetRepository->getFleetByUser($userId);
        static::assertCount(2, $fleet->getShips());
        static::assertSame(2, $fleet->getShipsByModel('Avenger')['00000000-0000-0000-0000-000000000010']->getQuantity());
        static::assertSame(2, $fleet->getShipsByModel('Mercury Star Runner')['00000000-0000-0000-0000-000000000011']->getQuantity());
    }
}
