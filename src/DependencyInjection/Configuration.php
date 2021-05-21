<?php


namespace Bytes\UserBundle\DependencyInjection;

use Bytes\UserBundle\Entity\CommandUserInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Bytes\UserBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('bytes_user');

        $treeBuilder->getRootNode()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('user_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Fully qualified classname for user entity that implements CommandUserInterface')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !(class_exists($value) && is_subclass_of($value, CommandUserInterface::class));
                        })
                        ->thenInvalid('Class "%s" does not exist or does not implement CommandUserInterface')
                    ->end()
                ->end()
                ->arrayNode('entity')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('identifier')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('The username/identifier field in the User entity class defined under user_class')
                            ->defaultValue('username')
                        ->end()
                        ->scalarNode('email')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('The email address field in the User entity class defined under user_class')
                            ->defaultValue('email')
                        ->end()
                        ->scalarNode('password')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('The password field in the User entity class defined under user_class')
                            ->defaultValue('password')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('super_admin_role')
                    ->cannotBeEmpty()
                    ->info('The role given to super administrators')
                    ->defaultValue('ROLE_SUPER_ADMIN')
                ->end()
            ->end();

        return $treeBuilder;
    }
}