<?php

namespace App\Controller\BackOffice\ShipTransform;

use App\Entity\ShipName;
use App\Repository\ShipNameRepository;
use App\Repository\ShipRepository;
use App\Service\Ship\InfosProvider\ShipInfosProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncFleetShipsWithProviderController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ShipInfosProviderInterface $shipInfosProvider;
    private ShipRepository $shipRepository;
    private EntityManagerInterface $entityManager;
    private ShipNameRepository $shipNameRepository;

    public function __construct(
        ShipInfosProviderInterface $shipInfosProvider,
        ShipRepository $shipRepository,
        EntityManagerInterface $entityManager,
        ShipNameRepository $shipNameRepository
    ) {
        $this->shipInfosProvider = $shipInfosProvider;
        $this->shipRepository = $shipRepository;
        $this->entityManager = $entityManager;
        $this->shipNameRepository = $shipNameRepository;
    }

    /**
     * @Route("/bo/sync-fleet-ships-with-provider", name="bo_sync_fleet_ships_with_provider", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $totalCountShips = $this->shipRepository->countFromLastFleetWithoutGalaxyId();
        $this->logger->info('[SyncFleetShipsWithProvider] refreshing {countShips} ships.', ['countShips' => $totalCountShips]);

        /** @var ShipName[] $shipMappings */
        $shipMappings = $this->shipNameRepository->findAllMappingsWithPatternAndProviderId();

        // see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/batch-processing.html#batch-processing
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->entityManager->clear();
        $lastId = null;
        $sumCountShips = 0;
        do {
            $ships = $this->shipRepository->findFromLastFleetWithoutGalaxyId($lastId, 1000);
            $countShips = count($ships);
            $sumCountShips += $countShips;

            $notFoundMappingNames = [];
            $providerIds = [];
            foreach ($ships as $ship) {
                $shipName = mb_strtolower(trim($ship->getName()));
                $mappingFound = false;
                foreach ($shipMappings as $shipMapping) {
                    if (!preg_match('~'.str_replace('~', '\~', $shipMapping->getMyHangarNamePattern()).'~i', $shipName)) {
                        continue;
                    }
                    // found a mapping
                    $providerIds[$shipMapping->getProviderId()->toString()] = $ship; // for request all ship infos all-in-once
                    $mappingFound = true;
                    break;
                }
                if (!$mappingFound) {
                    $notFoundMappingNames[$shipName] = $ship; // for trying to get a ship info by the name
                }
            }

            $shipInfos = $this->shipInfosProvider->getShipsByIdOrName(array_keys($providerIds), array_keys($notFoundMappingNames));

            foreach ($providerIds as $providerId => $ship) {
                $foundShipInfo = null;
                foreach ($shipInfos as $shipInfo) {
                    if ($shipInfo->id === $providerId) {
                        $foundShipInfo = $shipInfo;
                        break;
                    }
                }
                if ($foundShipInfo === null) {
                    $this->logger->warning('[SyncFleetShipsWithProvider] The ship with provider Id {providerId} was not found. Check the ship mapping in BO.', ['providerId' => $providerId]);
                    continue;
                }
                $ship->setNormalizedName($foundShipInfo->name);
                $ship->setGalaxyId($foundShipInfo->id !== null ? Uuid::fromString($foundShipInfo->id) : null);
            }
            $collator = \Collator::create(null);
            $collator->setStrength(\Collator::PRIMARY); // first level compares for example : a === Â, but a < b
            foreach ($notFoundMappingNames as $shipName => $ship) {
                $foundShipInfo = null;
                foreach ($shipInfos as $shipInfo) {
                    if ($collator->compare($shipInfo->name, $shipName) === 0) {
                        $foundShipInfo = $shipInfo;
                        break;
                    }
                }
                if ($foundShipInfo === null) {
                    $this->logger->warning('[SyncFleetShipsWithProvider] The ship with name "{shipName}" was not found. You should add it to ship mapping in BO.', ['shipName' => $shipName]);
                    continue;
                }
                $ship->setNormalizedName($foundShipInfo->name);
                $ship->setGalaxyId($foundShipInfo->id !== null ? Uuid::fromString($foundShipInfo->id) : null);
            }

            $lastId = end($ships)->getId();

            $this->entityManager->flush();
            $this->entityManager->clear();
            $this->logger->info('[SyncFleetShipsWithProvider] {countShips} ships synced with Galaxy.', ['countShips' => $countShips, 'sumCountShips' => $sumCountShips]);
        } while ($countShips >= 1000);

        return $this->redirectToRoute('bo_ship_transform_list');
    }
}
