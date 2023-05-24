<?php

namespace Bytes\UserBundle\Tests\Command;

use Bytes\Tests\Common\TestExtractorTrait;
use Bytes\Tests\Common\TestValidatorTrait;
use Bytes\UserBundle\Command\UserChangePasswordCommand;
use Bytes\UserBundle\Command\UserPromoteCommand;
use Bytes\UserBundle\Entity\CommandUserInterface;
use Bytes\UserBundle\Tests\Fixtures\Models\User;
use Bytes\UserBundle\Tests\Fixtures\UserPasswordHasherInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

/**
 * Class UserChangePasswordCommandTest
 * @package Bytes\UserBundle\Tests\Command
 */
class UserChangePasswordCommandTest extends TestCase
{
    use TestExtractorTrait, TestValidatorTrait;

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     * @throws Exception
     */
    public function testUserChangePasswordCommandExecute($manager, $encoder, $userClass, $accessor)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $encoder->expects($this->once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', false, false, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'password' => 'abc123']);
        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode());

    }

    /**
     * @param User|null $user
     * @return ServiceEntityRepository
     */
    private function getMockRepo(?User $user = null)
    {
        $repo = $this->getMockBuilder(ServiceEntityRepository::class)->disableOriginalConstructor()->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        return $repo;
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     */
    public function testUserChangePasswordCommandExecuteInvalidUser($manager, $encoder, $userClass, $accessor)
    {
        $repo = $this->getMockRepo();

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', false, false, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);

        $tester->execute(['useridentifier' => 'john', 'password' => 'abc123']);
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     */
    public function testUserChangePasswordCommandExecuteBlankPassword($manager, $encoder, $userClass, $accessor)
    {
        $repo = $this->getMockRepo();

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', false, false, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);

        $tester->execute(['useridentifier' => 'john', 'password' => ' ']);
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     */
    public function testUserChangePasswordCommandExecuteCompromisedPasswordSuccess($manager, $encoder, $userClass, $accessor)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $encoder->expects($this->once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', true, false, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'password' => 'gdfhoLkh435lhdfglksdr384tg;lkdhfrgkljdfhsg']);
        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     */
    public function testUserChangePasswordCommandExecuteCompromisedPasswordFailure($manager, $encoder, $userClass, $accessor)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $encoder->expects($this->once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', true, false, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'password' => 'abc123']);
        $this->assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @requires function \Symfony\Component\Validator\Constraints\PasswordStrength::__construct
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     */
    public function testUserChangePasswordCommandExecutePasswordStrengthSuccess($manager, $encoder, $userClass, $accessor)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $encoder->expects($this->once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', false, true, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'password' => 'gdfhoLkh435lhdfglksdr384tg;lkdhfrgkljdfhsg']);
        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @requires function \Symfony\Component\Validator\Constraints\PasswordStrength::__construct
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @param $accessor
     */
    public function testUserChangePasswordCommandExecutePasswordStrengthFailure($manager, $encoder, $userClass, $accessor)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $encoder->expects($this->once())
            ->method('hashPassword')
            ->willReturnArgument(1);

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', false, true, 2, $encoder, $this->createValidator(), $repo);
        $command->setAccessor($accessor);
        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'password' => 'abc123']);
        $this->assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @return Generator
     */
    public function provideMocks()
    {
        $this->setupExtractorParts();
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $encoder = $this->getMockBuilder(UserPasswordHasherInterface::class)->getMock();
        $userClass = $this->getMockBuilder(CommandUserInterface::class)->getMock();
        $accessor = $this->propertyAccessor;

        yield ['manager' => $manager, 'encoder' => $encoder, 'userClass' => $userClass, 'accessor' => $accessor];
    }

    /**
     * @dataProvider provideCompletionSuggestions
     */
    public function testComplete(array $input, array $expectedSuggestions)
    {
        $user = User::random('john');
        $user->setRoles(array_merge($user->getRoles(), ['ROLE_USER', 'ROLE_TEST']));
        $repo = $this->getMockRepoAll($user, $this->once());

        foreach ($this->provideMocks() as $mocks) {
            list('manager' => $manager, 'userClass' => $userClass, 'accessor' => $accessor, 'encoder' => $encoder) = $mocks;

            $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', false, false, 2, $encoder, $this->createValidator(), $repo);
            $command->setAccessor($accessor);

            $tester = new CommandCompletionTester($command);

            $suggestions = $tester->complete($input);

            foreach ($expectedSuggestions as $expectedSuggestion) {
                $this->assertContains($expectedSuggestion, $suggestions);
            }
        }
    }

    /**
     * @return Generator
     */
    public function provideCompletionSuggestions(): Generator
    {
        yield 'search' => [[''], ['john']];
        yield 'search j' => [['j'], ['john']];
    }

    /**
     * @param User|null $user
     * @return ServiceEntityRepository
     */
    private function getMockRepoAll(?User $user = null, ?InvokedCount $expects = null)
    {
        $repo = $this->getMockBuilder(ServiceEntityRepository::class)->disableOriginalConstructor()->getMock();

        $repo->expects($expects)
            ->method('findAll')
            ->willReturn([$user]);

        return $repo;
    }
}