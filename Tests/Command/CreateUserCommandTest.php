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

        $command = new CreateUserCommand(
            $manager, User::class, 'username', 'email', 'password', ['ROLE_ADMIN', 'ROLE_USER'], $encoder,
            $this->propertyInfo, $this->propertyAccessor, $this->createValidator(), $repo);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com']);
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

        $command = new CreateUserCommand(
            $manager, User::class, 'username', 'email', 'password', ['ROLE_ADMIN', 'ROLE_USER'], $encoder,
            $this->propertyInfo, $this->propertyAccessor, $this->createValidator(), $repo);
        $tester = new CommandTester($command);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com']);
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

        $command = new CreateUserCommand(
            $manager, User::class, 'username', 'email', 'password', ['ROLE_ADMIN', 'ROLE_USER'], $encoder,
            $this->propertyInfo, $this->propertyAccessor, $this->createValidator(), $repo);
        $tester = new CommandTester($command);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake.com']);
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

        $command = new CreateUserCommand(
            $manager, User::class, 'username', 'email', 'password', ['ROLE_ADMIN', 'ROLE_USER'], $encoder,
            $this->propertyInfo, $this->propertyAccessor, $this->createValidator(), $repo);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'email' => 'john@fake']);
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
}