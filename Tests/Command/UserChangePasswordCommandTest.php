<?php

namespace Bytes\UserBundle\Tests\Command;

use Bytes\UserBundle\Command\UserChangePasswordCommand;
use Bytes\UserBundle\Entity\CommandUserInterface;
use Bytes\UserBundle\Tests\Fixtures\Models\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserChangePasswordCommandTest
 * @package Bytes\UserBundle\Tests\Command
 */
class UserChangePasswordCommandTest extends TestCase
{
    /**
     * @dataProvider provideMocks
     * @param $manager
     * @param $encoder
     * @param $userClass
     * @throws Exception
     */
    public function testUserChangePasswordCommandExecute($manager, $encoder, $userClass)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);
        $encoder->expects($this->once())
            ->method('encodePassword')
            ->willReturnArgument(1);

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', $encoder, $repo);
        $tester = new CommandTester($command);

        $tester->execute(['username' => 'john', 'password' => 'abc123']);
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
     * @throws Exception
     */
    public function testUserChangePasswordCommandExecuteInvalidUser($manager, $encoder, $userClass)
    {
        $repo = $this->getMockRepo();

        $command = new UserChangePasswordCommand($manager, $userClass::class, 'username', $encoder, $repo);
        $tester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);

        $tester->execute(['username' => 'john', 'password' => 'abc123']);

    }

    /**
     * @return Generator
     */
    public function provideMocks()
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $encoder = $this->getMockBuilder(UserPasswordEncoderInterface::class)->getMock();
        $userClass = $this->getMockBuilder(CommandUserInterface::class)->getMock();

        yield ['manager' => $manager, 'encoder' => $encoder, 'userClass' => $userClass];
    }
}