<?php

namespace App\Tests\Acceptance\MyOrganizations;

use App\Application\MyOrganizations\CreateOrganizationService;
use App\Application\Repository\OrganizationRepositoryInterface;
use App\Domain\MemberId;
use App\Domain\OrgaId;
use App\Infrastructure\Repository\Organization\InMemoryOrganizationRepository;
use App\Tests\Acceptance\KernelTestCase;

class CreateOrganizationServiceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function it_should_create_an_orga_of_logged_user(): void
    {
        $memberId = MemberId::fromString('00000000-0000-0000-0000-000000000001');
        $orgaId = OrgaId::fromString('00000000-0000-0000-0000-000000000010');

        /** @var CreateOrganizationService $service */
        $service = static::$container->get(CreateOrganizationService::class);
        $service->handle($orgaId, $memberId, 'Force Coloniale Unifiée', 'fcu', 'https://example.org/picture.jpg');

        /** @var InMemoryOrganizationRepository $orgaRepository */
        $orgaRepository = static::$container->get(OrganizationRepositoryInterface::class);
        $orga = $orgaRepository->getOrganization($orgaId);
        static::assertNotNull($orga, 'Orga should be created.');
        static::assertSame('00000000-0000-0000-0000-000000000010', (string) $orga->getId());
    }
}