<?php


namespace Bytes\UserBundle\DependencyInjection;


use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Class BytesUserExtension
 * @package Bytes\UserBundle\DependencyInjection
 */
class BytesUserExtension extends Extension implements ExtensionInterface
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);

        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('bytes_user.command.user_change_password');
        $definition->replaceArgument(1, $config['user_class']);
        $definition->replaceArgument(2, $config['entity']['identifier']);

        $definition = $container->getDefinition('bytes_user.command.user_create');
        $definition->replaceArgument(1, $config['user_class']);
        $definition->replaceArgument(2, $config['entity']['identifier']);
        $definition->replaceArgument(3, $config['entity']['email']);
        $definition->replaceArgument(4, $config['entity']['password']);
        $definition->replaceArgument(5, $config['default_roles']);

        $definition = $container->getDefinition('bytes_user.command.user_promote');
        $definition->replaceArgument(1, $config['user_class']);
        $definition->replaceArgument(2, $config['entity']['identifier']);
        $definition->replaceArgument(3, $config['super_admin_role']);

        $definition = $container->getDefinition('bytes_user.command.user_demote');
        $definition->replaceArgument(1, $config['user_class']);
        $definition->replaceArgument(2, $config['entity']['identifier']);
        $definition->replaceArgument(3, $config['super_admin_role']);
    }
}