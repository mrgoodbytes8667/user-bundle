<?php

namespace Bytes\UserBundle\Command;

use Bytes\UserBundle\Entity\CommandUserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
 * Based on the FOSUserBundle role commands
 * @package Bytes\UserBundle\Command
 *
 * @license MIT
 * @link https://github.com/FriendsOfSymfony/FOSUserBundle
 */
abstract class RoleCommand extends AbstractUserCommand
{
    use RoleTrait, UsernameCompletionTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    private ArrayCollection $roles;

    /**
     * RoleCommand constructor.
     * @param EntityManagerInterface $manager
     * @param string $userClass
     * @param string $userIdentifier
     * @param string $superAdminRole
     * @param ServiceEntityRepository|null $repo
     */
    public function __construct(EntityManagerInterface $manager, string $userClass, string $userIdentifier, protected string $superAdminRole = 'ROLE_SUPER_ADMIN', ?ServiceEntityRepository $repo = null)
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('role', InputArgument::OPTIONAL, 'The role'),
                new InputOption('super', null, InputOption::VALUE_NONE, 'Instead specifying role, use this to quickly add the super administrator role'),
            ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        return parent::execute($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand(): int
    {
        $username = $this->input->getArgument('username');
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
            throw new InvalidArgumentException('The supplied username is not found.');
        }

        $this->executeRoleCommand($user, $super, $role);

        return static::SUCCESS;
    }

    /**
     * @see Command
     * @param UserInterface $user
     * @param bool $super
     * @param string $role
     *
     * @return mixed
     */
    abstract protected function executeRoleCommand(UserInterface $user, bool $super, string $role);

    /**
     * @param string $username
     * @return UserInterface|null
     */
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

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new Exception('Username can not be empty');
                }

                return $username;
            });
            $questions['username'] = $question;
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