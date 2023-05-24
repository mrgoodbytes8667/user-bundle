<?php

namespace Bytes\UserBundle\Command;

use Bytes\CommandBundle\Exception\CommandRuntimeException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function Symfony\Component\String\u;

/**
 * Class UserChangePasswordCommand
 * Based on the FOSUserBundle role commands
 * @package Bytes\UserBundle\Command
 *
 * @license MIT
 * @link https://github.com/FriendsOfSymfony/FOSUserBundle
 */
#[AsCommand('bytes:user:change-password', description: 'Change the password of a user.')]
class UserChangePasswordCommand extends AbstractUserCommand
{
    use UsernameCompletionTrait;

    /**
     * @param EntityManagerInterface $manager
     * @param string $userClass
     * @param string $userIdentifier
     * @param bool $validateNotCompromisedPassword
     * @param bool $validatePasswordStrength
     * @param int $validatePasswordStrengthMinScore
     * @param UserPasswordHasherInterface $encoder
     * @param ValidatorInterface $validator
     * @param ServiceEntityRepository|null $repo
     */
    public function __construct(EntityManagerInterface $manager, string $userClass, string $userIdentifier, private readonly bool $validateNotCompromisedPassword, private readonly bool $validatePasswordStrength, private readonly int $validatePasswordStrengthMinScore, private readonly UserPasswordHasherInterface $encoder, private readonly ValidatorInterface $validator, ?ServiceEntityRepository $repo = null)
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
            ->setDefinition([
                new InputArgument('useridentifier', InputArgument::REQUIRED, 'The user identifier'),
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
        $username = $this->input->getArgument('useridentifier');

        /** @var ServiceEntityRepository $repo */
        $repo = $this->repo;

        $user = $repo->findOneBy([$this->userIdentifier => $username]);

        if (empty($user)) {
            throw new InvalidArgumentException('The supplied user identifier is not found.');
        }

        $plainPassword = u($this->input->getArgument('password'))->trim()->toString();
        $validators = [
            new NotBlank()
        ];
        if ($this->validateNotCompromisedPassword) {
            $validators[] = new NotCompromisedPassword();
        }
        
        if ($this->validatePasswordStrength && class_exists(\Symfony\Component\Validator\Constraints\PasswordStrength::class)) {
            $validators[] = new \Symfony\Component\Validator\Constraints\PasswordStrength(minScore: $this->validatePasswordStrengthMinScore);
        }
        
        $errors = $this->validator->validate($plainPassword, $validators);
        if (count($errors) > 0) {
            $previous = new ValidatorException((string)$errors);
            throw new CommandRuntimeException($previous->getMessage(), displayMessage: true, code: $previous->getCode(), previous: $previous);
        }

        $password = $this->encoder->hashPassword($user, $plainPassword);
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

        if (!$input->getArgument('useridentifier')) {
            $question = new Question('Please provide the user identifier:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new Exception('User identifier can not be empty');
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
