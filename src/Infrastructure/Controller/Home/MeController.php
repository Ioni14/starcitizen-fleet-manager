<?php

namespace App\Infrastructure\Controller\Home;

use App\Application\Home\MeService;
use App\Application\Home\Output\MeOutput;
use App\Entity\User;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OpenApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

class MeController
{
    public function __construct(
        private MeService $meService,
        private Security $security,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @OpenApi\Tag(name="Home")
     * @OpenApi\Response(response=200, description="Returns summary infos about user.", @Model(type=MeOutput::class))
     */
    #[Route('/api/me', name: 'home_me', methods: ['GET'])]
    public function __invoke(
        Request $request
    ): Response {
        if (!$this->security->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        /** @var User $user */
        $user = $this->security->getUser();

        $output = $this->meService->handle($user->getId());

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}
