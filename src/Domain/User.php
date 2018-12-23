<?php

namespace App\Domain;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

class User implements UserInterface
{
    /**
     * @var UuidInterface
     *
     * @Groups({"profile"})
     */
    public $id;

    /**
     * @var string
     *
     * @Groups({"profile"})
     */
    public $username;

    /**
     * @var \DateTimeInterface
     *
     * @Groups({"profile"})
     */
    public $createdAt;

    /**
     * @var string
     *
     * @Groups({"profile"})
     */
    public $token;

    /**
     * @var Citizen
     *
     * @Groups({"profile"})
     */
    public $citizen;

    public function __construct(?UuidInterface $id, string $username)
    {
        $this->id = $id;
        $this->username = $username;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }
}
