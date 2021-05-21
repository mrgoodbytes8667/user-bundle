<?php


namespace Bytes\UserBundle\Command;


use function Symfony\Component\String\u;

/**
 * Trait RoleTrait
 * @package Bytes\UserBundle\Command
 */
trait RoleTrait
{
    /**
     * @param string $role
     * @return bool
     */
    protected function validateRoleName(string $role): bool
    {
        return u($role)->startsWith('ROLE_');
    }
}