<?php

namespace Bytes\UserBundle\Command;

use Bytes\UserBundle\Entity\CommandUserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RoleCommand
 * Based on the FOSUserBundle role commands.
 *
 * @license MIT
 *
 * @see https://github.com/FriendsOfSymfony/FOSUserBundle
 */
abstract class RoleCommand extends AbstractUserCommand
{
    use RoleTrait;
    use UsernameCompletionTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * RoleCommand constructor.
     */
    public function __construct(EntityManagerInterface $manager, string $userClass, string $userIdentifier, protected string $superAdminRole = 'ROLE_SUPER_ADMIN', ServiceEntityRepository $repo = null)
    {
        if (!is_subclass_of($userClass, CommandUserInterface::class)) {
            throw new InvalidArgumentException('The provided user class must implement "\Bytes\UserBundle\Entity\CommandUserInterface"');
        }

        parent::__construct($manager, $userClass, $userIdentifier, $repo);
    }

    /**
     * Adds suggestions to $suggestions for the current completion input (e.g. option or argument).
     */
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        $this->completeUsername($input, $suggestions);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('useridentifier', InputArgument::REQUIRED, 'The user identifier'),
                new InputArgument('role', InputArgument::OPTIONAL, 'The role'),
                new InputOption('super', null, InputOption::VALUE_NONE, 'Instead specifying role, use this to quickly add the super administrator role'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        return parent::execute($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand(): int
    {
        $username = $this->input->getArgument('useridentifier');
        $role = $this->input->getArgument('role');
        $super = (true === $this->input->getOption('super'));

        if (null !== $role && $super) {
            throw new InvalidArgumentException('You can pass either the role or the --super option (but not both simultaneously).');
        }

        if (null === $role && !$super) {
            throw new RuntimeException('Not enough arguments.');
        }

        $role = strtoupper($role);
        if (!$this->validateRoleName($role)) {
            throw new InvalidArgumentException('The supplied role name is not valid.');
        }

        $user = $this->findUser($username);
        if (empty($user)) {
            throw new InvalidArgumentException('The supplied user identifier is not found.');
        }

        $this->executeRoleCommand($user, $super, $role);

        return static::SUCCESS;
    }

    /**
     * @see Command
     */
    abstract protected function executeRoleCommand(UserInterface $user, bool $super, string $role);

    protected function findUser(string $username): ?UserInterface
    {
        $repo = $this->repo;

        $user = $repo->findOneBy([$this->userIdentifier => $username]);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        if (!$input->getArgument('useridentifier')) {
            $question = new Question('Please choose a user identifier:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new Exception('User identifier can not be empty');
                }

                return $username;
            });
            $questions['useridentifier'] = $question;
        }

        if ((true !== $input->getOption('super')) && !$input->getArgument('role')) {
            $question = new Question('Please choose a role:');
            $question->setValidator(function ($role) {
                if (empty($role)) {
                    throw new Exception('Role can not be empty');
                }

                return $role;
            });
            $questions['role'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }
}
