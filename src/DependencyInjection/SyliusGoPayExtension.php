<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SyliusGoPayExtension extends Extension implements PrependExtensionInterface
{
    public function load(
        array $configs,
        ContainerBuilder $container,
    ): void {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('winzou_state_machine')) {
            return;
        }
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/app'));
        $loader->load('state_machine.yml');
    }
}
