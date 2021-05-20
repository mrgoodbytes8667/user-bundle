<?php


namespace Bytes\UserBundle\DependencyInjection;

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