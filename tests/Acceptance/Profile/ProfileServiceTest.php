<?php

namespace App\Tests\Acceptance\Profile;

use App\Domain\Exception\NotFoundUserException;
use App\Application\Profile\Output\ProfileOutput;
use App\Application\Profile\ProfileService;
use App\Application\Repository\UserRepositoryInterface;
use App\Domain\UserId;
use App\Entity\User;
use App\Infrastructure\Repository\User\InMemoryUserRepository;
use App\Tests\Acceptance\KernelTestCase;

class ProfileServiceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function it_should_return_infos_of_logged_user(): void
    {
        /** @var InMemoryUserRepository $userRepository */
        $userRepository = static::$container->get(UserRepositoryInterface::class);
        $user = new User(UserId::fromString('00000000-0000-0000-0000-000000000001'), 'Ioni', null, new \DateTimeImmutable('2021-03-20T17:42:00+01:00'));
        $user->changeHandle('ioni_handle');
        $user->setCoins(5);
        $user->setSupporterVisible(false);
        $user->provideProfile('Ioni_nickname');
        $userRepository->setUsers([$user]);

        /** @var ProfileService $service */
        $service = static::$container->get(ProfileService::class);
        $output = $service->handle(UserId::fromString('00000000-0000-0000-0000-000000000001'));

        static::assertEquals(new ProfileOutput(
            id: UserId::fromString('00000000-0000-0000-0000-000000000001'),
            auth0Username: 'Ioni',
            nickname: 'Ioni_nickname',
            handle: 'ioni_handle',
            supporterVisible: false,
            coins: 5,
            createdAt: new \DateTimeImmutable('2021-03-20T16:42:00+00:00'),
        ), $output);
    }

    /**
     * @test
     */
    public function it_should_return_custom_nickname(): void
    {
        /** @var InMemoryUserRepository $userRepository */
        $userRepository = static::$container->get(UserRepositoryInterface::class);
        $user = new User(UserId::fromString('00000000-0000-0000-0000-000000000001'), 'Ioni', 'custom_nickname', new \DateTimeImmutable('2021-03-20T17:42:00+01:00'));
        $user->setCoins(5);
        $user->setSupporterVisible(false);
        $user->provideProfile('Ioni_nickname');
        $userRepository->setUsers([$user]);

        /** @var ProfileService $service */
        $service = static::$container->get(ProfileService::class);
        $output = $service->handle(UserId::fromString('00000000-0000-0000-0000-000000000001'));

        static::assertEquals(new ProfileOutput(
            id: UserId::fromString('00000000-0000-0000-0000-000000000001'),
            auth0Username: 'Ioni',
            nickname: 'custom_nickname',
            handle: null,
            supporterVisible: false,
            coins: 5,
            createdAt: new \DateTimeImmutable('2021-03-20T16:42:00+00:00'),
        ), $output);
    }

    /**
     * @test
     */
    public function it_should_throw_exception_for_unknown_user(): void
    {
        $this->expectException(NotFoundUserException::class);

        /** @var ProfileService $service */
        $service = static::$container->get(ProfileService::class);
        $service->handle(UserId::fromString('00000000-0000-0000-0000-000000000001'));
    }
}
