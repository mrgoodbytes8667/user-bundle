<?php


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bytes\UserBundle\Command\CreateUserCommand;
use Bytes\UserBundle\Command\UserChangePasswordCommand;
use Bytes\UserBundle\Command\UserDemoteCommand;
use Bytes\UserBundle\Command\UserPromoteCommand;
use function Symfony\Component\String\u;

/**
 * @param ContainerConfigurator $container
 */
return static function (ContainerConfigurator $container) {

    $services = $container->services();

    $services->set('bytes_user.command.user_change_password', UserChangePasswordCommand::class)
        ->args([
            service('doctrine.orm.default_entity_manager'), // Doctrine\ORM\EntityManagerInterface
            '',
            '',
            service('security.password_hasher'), // Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface
            service('property_accessor'),
        ])
        ->tag('console.command', ['command' => 'bytes:user:change-password']);

    $services->set('bytes_user.command.user_create', CreateUserCommand::class)
        ->args([
            service('doctrine.orm.default_entity_manager'), // Doctrine\ORM\EntityManagerInterface
            '', // user_class
            '', // user_identifier
            '', // user_email
            '', // user_password
            [], // default_roles
            service('security.password_hasher'), // \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface
            service('property_info'),
            service('property_accessor'),
            service('validator'),
        ])
        ->tag('console.command', ['command' => 'bytes:user:create']);

    $services->set('bytes_user.command.user_promote', UserPromoteCommand::class)
        ->args([
            service('doctrine.orm.default_entity_manager'), // Doctrine\ORM\EntityManagerInterface
            '',
            '',
            '',
        ])
        ->tag('console.command', ['command' => 'bytes:user:promote']);

    $services->set('bytes_user.command.user_demote', UserDemoteCommand::class)
        ->args([
            service('doctrine.orm.default_entity_manager'), // Doctrine\ORM\EntityManagerInterface
            '',
            '',
            '',
        ])
        ->tag('console.command', ['command' => 'bytes:user:demote']);
};