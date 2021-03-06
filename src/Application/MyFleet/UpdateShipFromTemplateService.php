<?php

namespace App\Application\MyFleet;

use App\Application\Common\Clock;
use App\Application\Provider\ListTemplatesProviderInterface;
use App\Application\Repository\FleetRepositoryInterface;
use App\Domain\Exception\NotFoundShipTemplateByUserException;
use App\Domain\ShipId;
use App\Domain\ShipTemplateId;
use App\Domain\UserId;
use App\Entity\Fleet;

class UpdateShipFromTemplateService
{
    public function __construct(
        private FleetRepositoryInterface $fleetRepository,
        private ListTemplatesProviderInterface $listTemplatesProvider,
        private Clock $clock,
    ) {
    }

    public function handle(UserId $userId, ShipId $shipId, ShipTemplateId $templateId, ?int $quantity = null): void
    {
        $fleet = $this->fleetRepository->getFleetByUser($userId);
        if ($fleet === null) {
            $fleet = new Fleet($userId, $this->clock->now());
        }

        $template = $this->listTemplatesProvider->getShipTemplateOfUser($templateId, $userId);
        if ($template === null) {
            throw new NotFoundShipTemplateByUserException($userId, $templateId);
        }

        $fleet->updateShipFromTemplate($shipId, $template, $quantity ?? 1, $this->clock->now());

        $this->fleetRepository->save($fleet);
    }
}
