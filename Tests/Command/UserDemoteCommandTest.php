<?php

namespace Bytes\UserBundle\Tests\Command;

use Bytes\Tests\Common\TestExtractorTrait;
use Bytes\UserBundle\Command\UserDemoteCommand;
use Bytes\UserBundle\Entity\CommandUserInterface;
use Bytes\UserBundle\Tests\Fixtures\Models\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;

class UserDemoteCommandTest extends TestCase
{
    use TestExtractorTrait;

    /**
     * @dataProvider provideMocks
     *
     * @throws Exception
     */
    public function testUserDemoteCommandExecute($manager, $userClass, $accessor)
    {
        $user = User::random('john');
        $repo = $this->getMockRepo($user);

        $command = new UserDemoteCommand($manager, $userClass::class, 'username', repo: $repo);
        $command->setAccessor($accessor);

        $tester = new CommandTester($command);

        $tester->execute(['useridentifier' => 'john', 'role' => 'ROLE_TEST']);
        self::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @return ServiceEntityRepository
     */
    private function getMockRepo(User $user = null)
    {
        $repo = $this->getMockBuilder(ServiceEntityRepository::class)->disableOriginalConstructor()->getMock();

        $repo->expects(self::once())
            ->method('findOneBy')
            ->willReturn($user);

        return $repo;
    }

    /**
     * @dataProvider provideCompletionSuggestions
     */
    public function testComplete(array $input, array $expectedSuggestions)
    {
        $user = User::random('john');
        $user->setRoles(array_merge($user->getRoles(), ['ROLE_USER', 'ROLE_TEST']));

        $repo = $this->getMockBuilder(ServiceEntityRepository::class)->disableOriginalConstructor()->getMock();

        $repo->method('findOneBy')
            ->willReturn($user);
        $repo->method('findAll')
            ->willReturn([$user]);

        foreach ($this->provideMocks() as $generator) {
            list('manager' => $manager, 'userClass' => $userClass, 'accessor' => $accessor) = $generator;

            $command = new UserDemoteCommand($manager, $userClass::class, 'username', repo: $repo);
            $command->setAccessor($accessor);

            $tester = new CommandCompletionTester($command);

            $suggestions = $tester->complete($input);

            foreach ($expectedSuggestions as $expectedSuggestion) {
                self::assertContains($expectedSuggestion, $suggestions);
            }
        }
    }

    /**
     * @return Generator
     */
    public function provideMocks()
    {
        $this->setupExtractorParts();
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $userClass = $this->getMockBuilder(CommandUserInterface::class)->getMock();
        $accessor = $this->propertyAccessor;

        yield ['manager' => $manager, 'userClass' => $userClass, 'accessor' => $accessor];
    }

    public function provideCompletionSuggestions(): Generator
    {
        yield 'search' => [[''], ['john']];
        yield 'search j' => [['j'], ['john']];
        yield 'role R' => [['john', 'R'], ['ROLE_USER', 'ROLE_TEST']];
        yield 'role' => [['john', ''], ['ROLE_USER', 'ROLE_TEST']];
        yield 'role ROLE_U' => [['john', 'ROLE_U'], ['ROLE_USER']];
    }
}
