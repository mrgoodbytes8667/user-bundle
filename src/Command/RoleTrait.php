<?php

namespace Bytes\UserBundle\Command;

use function Symfony\Component\String\u;

/**
 * Trait RoleTrait.
 */
trait RoleTrait
{
    protected function validateRoleName(string $role): bool
    {
        return u($role)->startsWith('ROLE_');
    }
}
