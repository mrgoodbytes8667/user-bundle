<?php


namespace Bytes\UserBundle\Tests\Fixtures;


use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface as BaseUserPasswordHasherInterface;

/**
 * Interface UserPasswordHasherInterface
 * The Symfony interface declares this via @method calls which breaks the mock
 * @package Bytes\UserBundle\Tests\Fixtures
 */
interface UserPasswordHasherInterface extends BaseUserPasswordHasherInterface
{
    /**
     * Hashes the plain password for the given user.
     * @param PasswordAuthenticatedUserInterface $user
     * @param string $plainPassword
     * @return string
     */
    public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string;
}