<?php

namespace App\Controller\BackOffice\Funding;

use App\Domain\MonthlyCostCoverageId;
use App\Entity\MonthlyCostCoverage;
use App\Repository\MonthlyCostCoverageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;

class MonthlyCostCoverageListController extends AbstractController
{
    public function __construct(
        private MonthlyCostCoverageRepository $monthlyCostCoverageRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route("/bo/monthly-cost-coverage/list", name: "bo_monthly_cost_coverage_list", methods: ["GET"])]
    public function __invoke(
        Request $request
    ): Response {
        /** @var MonthlyCostCoverage[] $costCoverages */
        $costCoverages = $this->monthlyCostCoverageRepository->findBy([], ['month' => 'asc']);
        $defaultCostCoverage = null;
        foreach ($costCoverages as $i => $costCoverage) {
            if ($costCoverage->isDefault()) {
                $defaultCostCoverage = $costCoverage;
                unset($costCoverages[$i]);
                break;
            }
        }

        if ($defaultCostCoverage === null) {
            $defaultCostCoverage = (new MonthlyCostCoverage(new MonthlyCostCoverageId(new Ulid()), new \DateTimeImmutable(MonthlyCostCoverage::DEFAULT_DATE)))
                ->setPostpone(false);
            $this->entityManager->persist($defaultCostCoverage);
            $this->entityManager->flush();
        }

        return $this->render('back_office/funding/monthly_cost_coverage_list.html.twig', [
            'default_cost_coverage' => $defaultCostCoverage,
            'monthly_cost_coverages' => $costCoverages,
        ]);
    }
}
