<?php

namespace Bytes\UserBundle\Command;

use Bytes\UserBundle\Entity\CommandUserInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserDemoteCommand
 * Based on the FOSUserBundle role commands.
 *
 * @license MIT
 *
 * @see https://github.com/FriendsOfSymfony/FOSUserBundle
 */
#[AsCommand('bytes:user:demote', description: 'Demote a user by removing a role')]
class UserDemoteCommand extends RoleCommand
{
    /**
     * Adds suggestions to $suggestions for the current completion input (e.g. option or argument).
     */
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        parent::complete($input, $suggestions);

        if ($input->mustSuggestArgumentValuesFor('role')) {
            $user = $this->findUser($input->getArgument('useridentifier'));

            $suggestions->suggestValues($user->getRoles());

            unset($this->roles);
        }
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setHelp(<<<'EOT'
The <info>bytes:user:demote</info> command demotes a user by removing a role
  <info>php %command.full_name% john.doe ROLE_CUSTOM</info>
  <info>php %command.full_name% --super john.doe</info>
EOT
            );
    }

    /**
     * @param CommandUserInterface $user
     *
     * @return mixed|void
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function executeRoleCommand(UserInterface $user, bool $super, string $role)
    {
        if ($super) {
            if ($user->hasRole($this->superAdminRole)) {
                $user->removeRole($this->superAdminRole);
                $this->entityManager->flush();
                $this->output->writeln(sprintf('User "%s" has been demoted as a simple user. This change will not apply until the user logs out and back in again.', $user->getUserIdentifier()));
            } else {
                $this->output->writeln(sprintf('User "%s" is already a super administrator.', $user->getUserIdentifier()));
            }
        } else {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
                $this->entityManager->flush();
                $this->output->writeln(sprintf('Role "%s" has been removed from user "%s". This change will not apply until the user logs out and back in again.', $role, $user->getUserIdentifier()));
            } else {
                $this->output->writeln(sprintf('User "%s" did not have "%s" role .', $user->getUserIdentifier(), $role));
            }
        }
    }
}
