<?php

namespace Bytes\UserBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserChangePasswordCommand
 * Based on the FOSUserBundle role commands
 * @package Bytes\UserBundle\Command
 *
 * @license MIT
 * @link https://github.com/FriendsOfSymfony/FOSUserBundle
 */
class UserChangePasswordCommand extends AbstractUserCommand
{
    use UsernameCompletionTrait;

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'bytes:user:change-password';

    /**
     * @param EntityManagerInterface $manager
     * @param string $userClass
     * @param string $userIdentifier
     * @param UserPasswordHasherInterface $encoder
     * @param ServiceEntityRepository|null $repo
     */
    public function __construct(EntityManagerInterface $manager, string $userClass, string $userIdentifier, private UserPasswordHasherInterface $encoder, ?ServiceEntityRepository $repo = null)
    {
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
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Change the password of a user.')
            ->setDefinition([
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
            ])
            ->setHelp(<<<'EOT'
The <info>bytes:user:change-password</info> command changes the password of a user:
  <info>php %command.full_name% john.doe@example.example</info>
This interactive shell will first ask you for a password.
You can alternatively specify the password as a second argument:
  <info>php %command.full_name% john.doe mypassword</info>
EOT
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function executeCommand(): int
    {
        $username = $this->input->getArgument('username');

        /** @var ServiceEntityRepository $repo */
        $repo = $this->repo;

        $user = $repo->findOneBy([$this->userIdentifier => $username]);

        if (empty($user)) {
            throw new InvalidArgumentException('The supplied username is not found.');
        }

        $password = $this->encoder->hashPassword($user, $this->input->getArgument('password'));
        $user->setPassword($password);

        $this->entityManager->flush();

        $this->io->writeln(sprintf('Changed password for user <comment>%s</comment>', $username));

        return static::SUCCESS;
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        if (!$input->getArgument('username')) {
            $question = new Question('Please give the username:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new Exception('Username can not be empty');
                }

                return $username;
            });
            $questions['username'] = $question;
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please enter the new password:');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new Exception('Password can not be empty');
                }

                return $password;
            });
            $question->setHidden(true);
            $questions['password'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }
}