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
                ->arrayNode('default_roles')
                    ->scalarPrototype()->end()
                    ->defaultValue(['ROLE_USER'])
                ->end()
                ->scalarNode('super_admin_role')
                    ->cannotBeEmpty()
                    ->info('The role given to super administrators')
                    ->defaultValue('ROLE_SUPER_ADMIN')
                ->end()
                ->arrayNode('password_validation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('not_compromised')
                            ->info('Should the Not Compromised Password validation be run when using the change password command?')
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('password_strength')
                            ->info('Should the Password Strength validation be run when using the change password command?')
                            ->defaultValue(false)
                        ->end()
                        ->integerNode('password_strength_min_score')
                            ->info('The minimum score for the Password Strength validation.')
                            ->defaultValue(2)
                            ->validate()
                                ->ifNotInArray([1, 2, 3, 4])
                                ->thenInvalid('Strength level "%d" is not valid. Please pick a number between 1 (weakest) and 4 (strongest)')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}