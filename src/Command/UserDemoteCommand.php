<?php

namespace Bytes\UserBundle\Command;

use Bytes\UserBundle\Entity\CommandUserInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserDemoteCommand
 * Based on the FOSUserBundle role commands
 * @package Bytes\UserBundle\Command
 *
 * @license MIT
 * @link https://github.com/FriendsOfSymfony/FOSUserBundle
 */
class UserDemoteCommand extends RoleCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'bytes:user:demote';

    /**
     * Adds suggestions to $suggestions for the current completion input (e.g. option or argument).
     */
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        parent::complete($input, $suggestions);

        if ($input->mustSuggestArgumentValuesFor('role')) {
            $user = $this->findUser($input->getArgument('username'));

            $suggestions->suggestValues($user->getRoles());

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
            ->setDescription('Demote a user by removing a role')
            ->setHelp(<<<'EOT'
The <info>bytes:user:demote</info> command demotes a user by removing a role
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
            if ($user->hasRole($this->superAdminRole)) {
                $user->removeRole($this->superAdminRole);
                $this->entityManager->flush();
                $this->output->writeln(sprintf('User "%s" has been demoted as a simple user. This change will not apply until the user logs out and back in again.', $user->getUsername()));
            } else {
                $this->output->writeln(sprintf('User "%s" is already a super administrator.', $user->getUsername()));
            }
        } else {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
                $this->entityManager->flush();
                $this->output->writeln(sprintf('Role "%s" has been removed from user "%s". This change will not apply until the user logs out and back in again.', $role, $user->getUsername()));
            } else {
                $this->output->writeln(sprintf('User "%s" did not have "%s" role .', $user->getUsername(), $role));
            }
        }
    }
}