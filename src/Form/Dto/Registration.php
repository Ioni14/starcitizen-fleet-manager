<?php

namespace App\Form\Dto;

use App\Entity\User;
use App\Validator\Constraints\UniqueField;
use Symfony\Component\Validator\Constraints as Assert;

class Registration
{
    #[Assert\NotBlank(message: "Please enter your email.")]
    #[Assert\Email]
    #[UniqueField(entityClass: User::class, field: "email", message: "This email is already taken. Please choose another.")]
    public ?string $email;

    #[Assert\NotBlank(message: "Please choose a password.")]
    #[Assert\Length(min: 6, minMessage: "Some extra characters and you'll have the 6 required. ;-)")]
    public ?string $password;
}
