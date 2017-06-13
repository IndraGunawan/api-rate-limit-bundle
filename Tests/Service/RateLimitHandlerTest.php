<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\Service;

use Indragunawan\ApiRateLimitBundle\Service\RateLimitHandler;
use Indragunawan\ApiRateLimitBundle\Tests\Fixtures\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RateLimitHandlerTest extends TestCase
{
    public function testDisabledApiRateLimit()
    {
        $cache = $this->getMockBuilder(\Doctrine\Common\Cache\RedisCache::class)->getMock();
        // $decoder->expects($this->any())
        //     ->method('decode')
        //     ->will($this->returnValue($request->getContent()));

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', FOO::class);

        $rateLimitHandler = new RateLimitHandler($cache, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());
        $this->assertSame([
            'limit' => 60,
            'remaining' => 59,
            'reset' => gmdate('U') + 60,
        ], $rateLimitHandler->getRateLimitInfo());
    }

    public function testRateLimitIsExceeded()
    {
        $resetTime = gmdate('U');

        $cache = $this->getMockBuilder(\Doctrine\Common\Cache\RedisCache::class)->getMock();
        $cache->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue([
                'limit' => 60,
                'remaining' => 0,
                'reset' => $resetTime,
            ]));

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', FOO::class);

        $rateLimitHandler = new RateLimitHandler($cache, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());
        $this->assertSame([
            'limit' => 60,
            'remaining' => 0,
            'reset' => $resetTime,
        ], $rateLimitHandler->getRateLimitInfo());
        $this->assertTrue($rateLimitHandler->isRateLimitExceeded());
    }

    public function testRateLimitIsNotExceeded()
    {
        $resetTime = gmdate('U');

        $cache = $this->getMockBuilder(\Doctrine\Common\Cache\RedisCache::class)->getMock();
        $cache->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue([
                'limit' => 60,
                'remaining' => 50,
                'reset' => $resetTime,
            ]));

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', FOO::class);

        $rateLimitHandler = new RateLimitHandler($cache, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());
        $this->assertSame([
            'limit' => 60,
            'remaining' => 49,
            'reset' => $resetTime,
        ], $rateLimitHandler->getRateLimitInfo());
        $this->assertFalse($rateLimitHandler->isRateLimitExceeded());
    }
}
