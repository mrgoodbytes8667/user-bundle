<?php

namespace Bytes\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface CommandUserInterface.
 */
interface CommandUserInterface extends UserInterface
{
    public function hasRole(string $role): bool;

    /**
     * @return $this
     */
    public function addRole(string $role);

    /**
     * @return $this
     */
    public function removeRole(string $role);

    /**
     * @return $this
     */
    public function setRoles(array $roles);
}
