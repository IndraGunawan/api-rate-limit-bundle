<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\DependencyInjection;

use Indragunawan\ApiRateLimitBundle\DependencyInjection\IndragunawanApiRateLimitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IndragunawanApiRateLimitExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var IndragunawanApiRateLimitExtension
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.cache_dir', '../../var/cache');

        $this->extension = new IndragunawanApiRateLimitExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->container, $this->extension);
    }

    public function testEventListenerConfig()
    {
        $config = [
            [
                'enabled' => false,
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.event_listener.rate_limit'));
        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.event_listener.header_modification'));

        $rateLimitDefinition = $this->container->getDefinition('indragunawan_api_rate_limit.event_listener.rate_limit');
        $this->assertFalse($rateLimitDefinition->getArgument(0));
    }

    public function testServiceConfig()
    {
        $config = [
            [
                'cache' => 'custom_cache',
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.service.rate_limit_handler'));

        $storageDefinition = $this->container->getDefinition('indragunawan_api_rate_limit.service.rate_limit_handler');
        $this->assertSame('custom_cache', (string) $storageDefinition->getArgument(0));
    }

    public function testDeprecatedServiceConfig()
    {
        $config = [
            [
                'storage' => 'custom_storage',
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.service.rate_limit_handler'));

        $storageDefinition = $this->container->getDefinition('indragunawan_api_rate_limit.service.rate_limit_handler');
        $this->assertSame('custom_storage', (string) $storageDefinition->getArgument(0)->getArgument(0));
    }

    public function testSortFirstMatch()
    {
        $roles = [
            'ROLE_ADMIN' => [
                'limit' => 100,
                'period' => 10,
            ],
            'ROLE_USER' => [
                'limit' => 10,
                'period' => 10,
            ],
        ];

        $config = [
            [
                'throttle' => [
                    'roles' => $roles,
                    'sort' => 'first-match',
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.service.rate_limit_handler'));

        $storageDefinition = $this->container->getDefinition('indragunawan_api_rate_limit.service.rate_limit_handler');
        $this->assertSame($roles, $storageDefinition->getArgument(3)['roles']);
    }

    public function testSortRateLimitAsc()
    {
        $roles = [
            'ROLE_ADMIN' => [
                'limit' => 100,
                'period' => 10,
            ],
            'ROLE_USER' => [
                'limit' => 10,
                'period' => 10,
            ],
        ];

        $config = [
            [
                'throttle' => [
                    'roles' => $roles,
                    'sort' => 'rate-limit-asc',
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.service.rate_limit_handler'));

        $storageDefinition = $this->container->getDefinition('indragunawan_api_rate_limit.service.rate_limit_handler');
        $this->assertSame(array_reverse($roles), $storageDefinition->getArgument(3)['roles']);
    }

    public function testSortRateLimitDesc()
    {
        $roles = [
            'ROLE_USER' => [
                'limit' => 10,
                'period' => 10,
            ],
            'ROLE_ADMIN' => [
                'limit' => 100,
                'period' => 10,
            ],
        ];

        $config = [
            [
                'throttle' => [
                    'roles' => $roles,
                    'sort' => 'rate-limit-desc',
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        $this->assertTrue($this->container->hasDefinition('indragunawan_api_rate_limit.service.rate_limit_handler'));

        $storageDefinition = $this->container->getDefinition('indragunawan_api_rate_limit.service.rate_limit_handler');
        $this->assertSame(array_reverse($roles), $storageDefinition->getArgument(3)['roles']);
    }
}
