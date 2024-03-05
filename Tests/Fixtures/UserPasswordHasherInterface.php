<?php

namespace Bytes\UserBundle\Tests\Fixtures;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface as BaseUserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Interface UserPasswordHasherInterface
 * The Symfony interface declares this via @method calls which breaks the mock.
 */
interface UserPasswordHasherInterface extends BaseUserPasswordHasherInterface
{
    /**
     * Hashes the plain password for the given user.
     */
    public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string;
}
