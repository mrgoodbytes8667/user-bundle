<?php

namespace Bytes\UserBundle\Command;

use Bytes\UserBundle\Entity\CommandUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserPromoteCommand
 * Based on the FOSUserBundle role commands
 * @package Bytes\UserBundle\Command
 *
 * @license MIT
 * @link https://github.com/FriendsOfSymfony/FOSUserBundle
 */
class UserPromoteCommand extends RoleCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'bytes:user:promote';

    private ArrayCollection $roles;

    /**
     * Adds suggestions to $suggestions for the current completion input (e.g. option or argument).
     */
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        parent::complete($input, $suggestions);

        if ($input->mustSuggestArgumentValuesFor('role')) {
            /** @var UserInterface[] $users */
            $users = $this->repo->findAll() ?? [];
            $this->roles = new ArrayCollection();

            foreach ($users as $user)
            {
                foreach ($user->getRoles() as $role)
                {
                    $this->addIfNotExists($role);
                }
            }
            $this->addIfNotExists($this->superAdminRole);

            $suggestions->suggestValues($this->roles->toArray());

            unset($this->roles);
        }
    }

    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Promotes a user by adding a role')
            ->setHelp(<<<'EOT'
The <info>bytes:user:promote</info> command promotes a user by adding a role
  <info>php %command.full_name% john.doe ROLE_CUSTOM</info>
  <info>php %command.full_name% --super john.doe</info>
EOT
            );
    }

    /**
     * @param CommandUserInterface $user
     * @param bool $super
     * @param string $role
     *
     * @return mixed|void
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function executeRoleCommand(UserInterface $user, bool $super, string $role)
    {
        if ($super) {
            if (!$user->hasRole($this->superAdminRole)) {
                $user->addRole($this->superAdminRole);
                $this->entityManager->flush();
                $this->output->writeln(sprintf('User "%s" has been promoted as a super administrator. This change will not apply until the user logs out and back in again.', $user->getUsername()));
            } else {
                $this->output->writeln(sprintf('User "%s" is already a super administrator.', $user->getUsername()));
            }
        } else {
            if (!$user->hasRole($role)) {
                $user->addRole($role);
                $this->entityManager->flush();
                $this->output->writeln(sprintf('Role "%s" has been added to user "%s". This change will not apply until the user logs out and back in again.', $role, $user->getUsername()));
            } else {
                $this->output->writeln(sprintf('User "%s" did already have "%s" role.', $user->getUsername(), $role));
            }
        }
    }

    /**
     * @param string $role
     */
    protected function addIfNotExists(string $role): void
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }
}