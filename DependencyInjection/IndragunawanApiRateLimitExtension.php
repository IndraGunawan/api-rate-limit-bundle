<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\DoctrineAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * The extension of this bundle.
 *
 * @author Indra Gunawan <hello@indra.my.id>
 */
final class IndragunawanApiRateLimitExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->registerEventListenerConfig($container, $config);
        $this->registerServiceConfig($container, $config);
    }

    private function registerEventListenerConfig(ContainerBuilder $container, array $config)
    {
        $container->getDefinition('indragunawan_api_rate_limit.event_listener.rate_limit')
            ->replaceArgument(0, $config['enabled'])
            ->replaceArgument(2, $config['exception']);

        $container->getDefinition('indragunawan_api_rate_limit.event_listener.header_modification')
            ->replaceArgument(0, $config['header']);
    }

    private function registerServiceConfig(ContainerBuilder $container, array $config)
    {
        if (null !== $config['cache']) {
            $cache = new Reference($config['cache']);
        } elseif (null !== $config['storage']) {
            @trigger_error('The indragunawan_api_rate_limit.storage configuration key is deprecated since version v0.2.0 and will be removed in v0.3.0. Use the indragunawan_api_rate_limit.cache configuration key instead.', E_USER_DEPRECATED);

            $cache = new Definition(DoctrineAdapter::class, [new Reference($config['storage']), 'api_rate_limit']);
        } else {
            $cache = new Definition(FilesystemAdapter::class, ['api_rate_limit', 0, $container->getParameter('kernel.cache_dir')]);
        }

        if ('rate-limit-asc' === $config['throttle']['sort']) {
            uasort($config['throttle']['roles'], function (array $a, array $b) {
                return ($a['limit'] / $a['period']) <=> ($b['limit'] / $b['period']);
            });
        } elseif ('rate-limit-desc' === $config['throttle']['sort']) {
            uasort($config['throttle']['roles'], function (array $a, array $b) {
                return ($b['limit'] / $b['period']) <=> ($a['limit'] / $a['period']);
            });
        }

        $container->getDefinition('indragunawan_api_rate_limit.service.rate_limit_handler')
            ->replaceArgument(0, $cache)
            ->replaceArgument(3, $config['throttle']);
    }
}
