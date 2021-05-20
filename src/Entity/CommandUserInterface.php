<?php


namespace Bytes\UserBundle\Entity;


interface CommandUserInterface extends \Symfony\Component\Security\Core\User\UserInterface
{
    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool;

    /**
     * @param string $role
     *
     * @return $this
     */
    public function addRole(string $role);

    /**
     * @param string $role
     *
     * @return $this
     */
    public function removeRole(string $role);
}