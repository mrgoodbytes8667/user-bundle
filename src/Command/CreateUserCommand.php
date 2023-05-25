<?php


namespace Bytes\UserBundle\Command;


use Bytes\UserBundle\Entity\CommandUserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class CreateUserCommand
 * @package Bytes\UserBundle\Command
 */
#[AsCommand('bytes:user:create', description: 'Create a user')]
class CreateUserCommand extends AbstractUserCommand
{
    use RoleTrait, PasswordValidationTrait;

    public function __construct(
        EntityManagerInterface                   $manager, string $userClass, string $userIdentifier, protected string $userEmail,
        protected string                         $userPassword, protected array $defaultRoles, private readonly UserPasswordHasherInterface $encoder,
        protected PropertyInfoExtractorInterface $extractor, protected PropertyAccessorInterface $accessor,
        ?ServiceEntityRepository                 $repo = null)
    {
        foreach ($defaultRoles as $role) {
            if (!$this->validateRoleName($role)) {
                throw new InvalidArgumentException('Default roles do not pass the validation test.');
            }
        }

        parent::__construct($manager, $userClass, $userIdentifier, $repo);

        if (!$extractor->isWritable($userClass, $userIdentifier)) {
            throw new InvalidArgumentException('The provided user class does not have a settable user identifier field.');
        }
    }

    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->addArgument('useridentifier', InputArgument::REQUIRED, 'User identifier')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addOption('generate-password', mode: InputOption::VALUE_NEGATABLE, description: 'Bypass prompt for password and auto-generate', default: false)
            ->setHelp(<<<'EOT'
The <info>bytes:user:create</info> command creates a user:

  <info>php %command.full_name% john</info>

You will be prompted for a user identifier, email address, and password if not specified as arguments.

EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        if (!$input->getArgument('useridentifier')) {
            $question = new Question('Please enter a user identifier:');
            $question->setValidator(function ($useridentifier) {
                if (empty($useridentifier)) {
                    throw new Exception('User identifier cannot be empty');
                }

                if ($this->repo->count([$this->userIdentifier => $useridentifier]) !== 0) {
                    throw new Exception('User identifier is already in use.');
                }

                return $useridentifier;
            });
            $questions['useridentifier'] = $question;
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please enter an email address:');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new Exception('Email address cannot be empty');
                }

                if ($this->repo->count([$this->userEmail => $email]) !== 0) {
                    throw new Exception('Email address is already in use.');
                }

                $errors = $this->validator->validate($email, [
                    new NotBlank(),
                    new Email()
                ]);
                if (count($errors) > 0) {
                    throw new ValidatorException((string)$errors);
                }

                return $email;
            });
            $questions['email'] = $question;
        }

        if ($input->getOption('generate-password')) {
            $input->setArgument('password', $this->generatePassword());
        } else {
            if (!$input->getArgument('password')) {
                $question = new Question('Please enter the new password (or leave blank to have one generated for you):');
                $question->setValidator(function ($password) {
                    if (empty($password)) {
                        $password = $this->generatePassword();
                    }

                    $this->validatePassword($password);

                    return $password;
                });
                $question->setHidden(true);
                $questions['password'] = $question;
            }
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeCommand(): int
    {
        $username = $this->input->getArgument('useridentifier');
        $email = $this->input->getArgument('email');
        $password = $this->input->getArgument('password');

        $errors = $this->validator->validate($email, [
            new NotBlank(),
            new Email()
        ]);
        if (count($errors) > 0) {
            $this->io->error((string)$errors);
            return static::FAILURE;
        }

        if ($this->repo->count([$this->userIdentifier => $username]) !== 0) {
            $this->io->error('User identifier is already in use.');
            return static::FAILURE;
        }

        if ($this->repo->count([$this->userEmail => $email]) !== 0) {
            $this->io->error('Email address is already in use.');
            return static::FAILURE;
        }

        $this->validatePassword($password);

        $class = $this->userClass;
        /** @var CommandUserInterface $user */
        $user = new $class();

        $this->accessor->setValue($user, $this->userIdentifier, $username);
        if ($this->extractor->isWritable($this->userClass, $this->userEmail)) {
            $this->accessor->setValue($user, $this->userEmail, $email);
        }

        if ($this->extractor->isWritable($this->userClass, $this->userPassword)) {
            $this->accessor->setValue($user, $this->userPassword, $this->encoder->hashPassword($user, $password));
        }

        $user->setRoles($this->defaultRoles);

        $user = $this->initializeUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $table = new Table($this->output);
        $table
            ->setHeaders(['User Identifier', 'Email', 'Generated Password'])
            ->setRows([
                [$username, $email, $this->input->getOption('generate-password') ? $password : '*****'],
            ])
            ->setStyle('borderless');
        $table->render();

        return static::SUCCESS;
    }

    /**
     * Overloadable method to set up any initial fields on the user. Must return the modified user.
     * @param UserInterface $user
     * @return UserInterface
     */
    protected function initializeUser(UserInterface $user)
    {
        return $user;
    }
}
