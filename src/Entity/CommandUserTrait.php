<?php


namespace Bytes\UserBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Arr;

/**
 * Trait CommandUserTrait
 * @package Bytes\UserBundle\Entity
 */
trait CommandUserTrait
{
    /**
     * @var array
     * @ORM\Column(type="json")
     */
    protected $roles = [];

    /**
     * @param string|string[] $role
     *
     * @return bool
     */
    public function hasRole(string|array $role): bool
    {
        $roles = Arr::wrap($role);
        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }
            if (in_array(strtoupper($role), $this->getRoles(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->roles ?? [];
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles ?? [];

        return $this;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function addRole(string $role)
    {
        $role = strtoupper($role);

        if (!in_array($role, $this->getRoles(), true)) {
            $roles = $this->getRoles();
            $roles[] = $role;
            $this->setRoles($roles);
        }

        return $this;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function removeRole(string $role)
    {
        $role = strtoupper($role);
        $roles = $this->getRoles();
        if (false !== $key = array_search(strtoupper($role), $roles, true)) {
            unset($roles[$key]);
            $roles = array_values($roles);
            $this->setRoles($roles);
        }

        return $this;
    }
}