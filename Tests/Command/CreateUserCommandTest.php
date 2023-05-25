<?php

namespace Bytes\UserBundle\Tests\Command;

use Bytes\Tests\Common\TestFullSerializerTrait;
use Bytes\Tests\Common\TestValidatorTrait;
use Bytes\UserBundle\Command\CreateUserCommand;
use Bytes\UserBundle\Tests\Fixtures\Models\User;
use Bytes\UserBundle\Tests\Fixtures\UserPasswordHasherInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class CreateUserCommandTest
 * @package Bytes\UserBundle\Tests\Command
 */
class CreateUserCommandTest extends TestCase
{
    use TestFullSerializerTrait, TestValidatorTrait;

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecute($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $encoder->expects(self::once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', 'password' => 'abc123']);
        self::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @param User|null $user
     * @return ServiceEntityRepository
     */
    private function getMockRepo(?User $user = null)
    {
        $repo = $this->getMockBuilder(ServiceEntityRepository::class)->disableOriginalConstructor()->getMock();

        return $repo;
    }

    /**
     * @param $manager
     * @param $encoder
     * @param ServiceEntityRepository $repo
     * @param $userIdentifier
     * @param string $userEmail
     * @param string $userPassword
     * @param array $defaultRoles
     * @param $propertyInfo
     * @param $accessor
     * @param $userClass
     * @param ValidatorInterface|null $validator
     * @return CommandTester
     */
    private function getCommandTester($manager, $encoder, ServiceEntityRepository $repo, $userIdentifier = 'username', string $userEmail = 'email', string $userPassword = 'password', array $defaultRoles = ['ROLE_ADMIN', 'ROLE_USER'], bool $validateNotCompromisedPassword = false, bool $validatePasswordStrength = false, int $minScore = 2, $propertyInfo = null, $accessor = null, $userClass = User::class, ?ValidatorInterface $validator = null): CommandTester
    {
        $command = new CreateUserCommand(manager: $manager, userClass: $userClass, userIdentifier: $userIdentifier, userEmail: $userEmail, userPassword: $userPassword, defaultRoles: $defaultRoles, encoder: $encoder,
            extractor: $propertyInfo ?? $this->propertyInfo, accessor: $accessor ?? $this->propertyAccessor, repo: $repo);
        $command->setValidator($validator ?? $this->createValidator());
        $command->setValidateNotCompromisedPassword($validateNotCompromisedPassword);
        $command->setValidatePasswordStrength($validatePasswordStrength);
        $command->setValidatePasswordStrengthMinScore($minScore);

        return new CommandTester($command);
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteGeneratePassword($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $encoder->expects(self::once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', '--generate-password' => true]);
        self::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteUsernameExists($manager, $encoder)
    {
        $repo = $this->getMockRepo();
        $repo->method('count')
            ->willReturn(1);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', '--generate-password' => true]);
        self::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteEmailExists($manager, $encoder)
    {
        $repo = $this->getMockRepo();
        $repo->method('count')
            ->will(self::onConsecutiveCalls(0, 1));

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', '--generate-password' => true]);
        self::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteInvalidEmail($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $encoder->expects(self::once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake', '--generate-password' => true]);
        self::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @return Generator
     */
    public function provideMocks()
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $encoder = $this->getMockBuilder(UserPasswordHasherInterface::class)->getMock();

        yield ['manager' => $manager, 'encoder' => $encoder];
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteNotCompromisedPassword($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo, validateNotCompromisedPassword: true);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', 'password' => 'gdfhoLkh435lhdfglksdr384tg;lkdhfrgkljdfhsg']);
        self::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteCompromisedPassword($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo, validateNotCompromisedPassword: true);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', 'password' => 'abc123']);
        self::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteStrongPassword($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo, validatePasswordStrength: true);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', 'password' => 'gdfhoLkh435lhdfglksdr384tg;lkdhfrgkljdfhsg']);
        self::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @throws Exception
     */
    public function testCreateUserCommandExecuteWeakPassword($manager, $encoder)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $repo->method('count')
            ->willReturn(0);

        $tester = $this->getCommandTester(manager: $manager, encoder: $encoder, repo: $repo, validatePasswordStrength: true);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com', 'password' => 'abc123']);
        self::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }
}
