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

use Indragunawan\ApiRateLimitBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configuration = new Configuration(false);
        $this->processor = new Processor();
    }

    public function testDisabledApiRateLimit()
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                [
                    'enabled' => false,
                ],
            ]
        );

        $this->assertFalse($config['enabled']);
    }

    public function testInvalidStatusCode()
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid status code "999"');

        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                [
                    'exception' => [
                        'status_code' => 999,
                    ],
                ],
            ]
        );
    }
}
