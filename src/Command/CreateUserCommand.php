<?php


namespace Bytes\UserBundle\Command;


use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class CreateUserCommand
 * @package Bytes\UserBundle\Command
 */
class CreateUserCommand extends AbstractUserCommand
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'bytes:user:create';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Create a user';

    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address')
        ->setHelp(<<<'EOT'
The <info>bytes:user:create</info> command creates a user:
  <info>php %command.full_name% john</info>
You will be prompted for a username and email address if not specified as arguments.
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

        if (!$input->getArgument('username')) {
            $question = new Question('Please enter a username:');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new \Exception('Username cannot be empty');
                }

                if($this->repo->count([$this->userIdentifier => $username]) !== 0)
                {
                    throw new \Exception('Username is already in use.');
                }

                return $username;
            });
            $questions['username'] = $question;
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please enter an email address:');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('Email address cannot be empty');
                }

                if($this->repo->count([$this->userEmail => $email]) !== 0)
                {
                    throw new \Exception('Email address is already in use.');
                }

                return $email;
            });
            $questions['email'] = $question;
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
        $username = $this->input->getArgument('username');
        $email = $this->input->getArgument('email');
        $password = ByteString::fromRandom(alphabet: '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz~!@#$%^&*()-_+?.,');

        $class = $this->userClass;
        $user = new $class();

        $this->accessor->setValue($user, $this->userIdentifier, $username);
        $this->accessor->setValue($user, $this->userEmail, $email);
        $this->accessor->setValue($user, $this->userPassword, $this->encoder->encodePassword($user, $password));

        $user = $this->initializeUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $table = new Table($this->output);
        $table
            ->setHeaders(['Username', 'Email', 'Generated Password'])
            ->setRows([
                [$username, $email, $password],
            ])
            ->setStyle('borderless')
        ;
        $table->render();

        return static::SUCCESS;
    }

    public function __construct(
        EntityManagerInterface $manager, string $userClass, string $userIdentifier, protected string $userEmail,
        protected string $userPassword, private UserPasswordEncoderInterface $encoder,
        protected PropertyInfoExtractorInterface $extractor, protected PropertyAccessorInterface $accessor,
        protected ValidatorInterface $validator, ?ServiceEntityRepository $repo = null)
    {
        $this->needsOutput = true;
        parent::__construct($manager, $userClass, $userIdentifier, $repo);

        if(!$extractor->isWritable($userClass, $userIdentifier) || !$extractor->isWritable($userClass, $userEmail) || !$extractor->isWritable($userClass, $userPassword))
        {
            throw new \InvalidArgumentException('The provided user class does not have settable username and/or email address fields.');
        }
    }

    /**
     * Overloadable method to setup any initial fields on the user. Must return the modified user.
     * @param UserInterface $user
     * @return UserInterface
     */
    protected function initializeUser(UserInterface $user)
    {
        return $user;
    }


}