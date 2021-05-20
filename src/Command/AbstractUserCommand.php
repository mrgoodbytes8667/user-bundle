<?php


namespace Bytes\UserBundle\Command;


use Bytes\CommandBundle\Command\BaseEntityManagerCommand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AbstractUserCommand
 * @package Bytes\UserBundle\Command
 */
abstract class AbstractUserCommand extends BaseEntityManagerCommand
{
    /**
     * AbstractUserCommand constructor.
     * @param EntityManagerInterface $manager
     * @param string $userClass
     * @param ServiceEntityRepository|null $repo
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     */
    public function __construct(EntityManagerInterface $manager, protected string $userClass, protected ?ServiceEntityRepository $repo = null, string $name = null)
    {
        if(!is_subclass_of($userClass, UserInterface::class))
        {
            throw new \InvalidArgumentException('The provided user class must implement "\Symfony\Component\Security\Core\User\UserInterface"');
        }
        parent::__construct($manager, $name);
        if (is_null($repo)) {
            $this->repo = $manager->getRepository($userClass);
        }
    }
}