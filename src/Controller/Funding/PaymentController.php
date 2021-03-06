<?php

namespace App\Controller\Funding;

use App\Domain\FundingId;
use App\Entity\Funding;
use App\Entity\User;
use App\Exception\UnableToCreatePaypalOrderException;
use App\Form\Dto\FundingPayment;
use App\Service\Funding\PaypalCheckoutInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentController extends AbstractController
{
    public function __construct(
        private Security $security,
        private PaypalCheckoutInterface $paypalCheckout,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/funding/payment', name: 'funding_payment', methods: ['POST'])]
    public function __invoke(
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var FundingPayment $fundingPayment */
        $fundingPayment = $this->serializer->deserialize($request->getContent(), FundingPayment::class, $request->getContentType());
        $this->validator->validate($fundingPayment);

        /** @var User $user */
        $user = $this->security->getUser();

        $funding = new Funding(new FundingId(new Ulid()), Funding::PAYPAL, Money::USD($fundingPayment->amount), new \DateTimeImmutable('now'));
        $funding->setUser($user);

        try {
            $this->paypalCheckout->create($funding, $user, $request->getLocale());
        } catch (UnableToCreatePaypalOrderException $e) {
            return $this->json([
                'error' => 'paypal_error',
                'paypalError' => $e->paypalError,
            ], 400);
        }

        $this->entityManager->persist($funding);
        $this->entityManager->flush();

        return $this->json($funding, 200, [], ['groups' => 'supporter']);
    }
}
