<?php

namespace Bytes\UserBundle\Command;

use Bytes\CommandBundle\Command\BaseEntityManagerCommand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AbstractUserCommand.
 */
abstract class AbstractUserCommand extends BaseEntityManagerCommand
{
    /**
     * AbstractUserCommand constructor.
     */
    public function __construct(EntityManagerInterface $manager, protected string $userClass, protected string $userIdentifier, protected ?ServiceEntityRepository $repo = null)
    {
        if (!is_subclass_of($userClass, UserInterface::class)) {
            throw new InvalidArgumentException('The provided user class must implement "\Symfony\Component\Security\Core\User\UserInterface"');
        }

        parent::__construct($manager);
        if (is_null($repo)) {
            $this->repo = $manager->getRepository($userClass);
        }
    }
}
