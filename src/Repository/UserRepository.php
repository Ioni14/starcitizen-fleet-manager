<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getByUsername(string $username): ?User
    {
        /** @var User $userEntity */
        $userEntity = $this->createQueryBuilder('u')
            ->addSelect('c')
            ->addSelect('co')
            ->addSelect('o')
            ->leftJoin('u.citizen', 'c')
            ->leftJoin('c.organizations', 'co')
            ->leftJoin('co.organization', 'o')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $userEntity;
    }

    public function getByDiscordId(string $id): ?User
    {
        /** @var User $userEntity */
        $userEntity = $this->createQueryBuilder('u')
            ->addSelect('c')
            ->addSelect('co')
            ->addSelect('o')
            ->leftJoin('u.citizen', 'c')
            ->leftJoin('c.organizations', 'co')
            ->leftJoin('co.organization', 'o')
            ->where('u.discordId = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $userEntity;
    }

    public function getByToken(string $token): ?User
    {
        return $this->findOneBy(['apiToken' => $token]);
    }
}
