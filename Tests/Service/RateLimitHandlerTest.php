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
use Indragunawan\ApiRateLimitBundle\Tests\Fixtures\Entity\DisableRateLimit;
use Indragunawan\ApiRateLimitBundle\Tests\Fixtures\Entity\EnableRateLimit;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

class RateLimitHandlerTest extends TestCase
{
    public function testDisabledApiRateLimit()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->never())
            ->method('isHit');

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->never())
            ->method('getItem');

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', DisableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertFalse($rateLimitHandler->isEnabled());
    }

    public function testEnabledApiRateLimit()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(false));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $throttleConfig);
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

        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(true));

        $cacheItem->expects($this->once())
            ->method('get')
            ->will($this->returnValue([
                'limit' => 60,
                'remaining' => 0,
                'reset' => $resetTime,
            ]));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());
        $rateLimitInfo = $rateLimitHandler->getRateLimitInfo();
        $this->assertSame(60, $rateLimitInfo['limit']);
        $this->assertSame(0, $rateLimitInfo['remaining']);
        $this->assertTrue($rateLimitHandler->isRateLimitExceeded());
    }

    public function testRateLimitIsNotExceeded()
    {
        $resetTime = gmdate('U');

        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(true));

        $cacheItem->expects($this->once())
            ->method('get')
            ->will($this->returnValue([
                'limit' => 60,
                'remaining' => 50,
                'reset' => $resetTime,
            ]));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $throttleConfig = [
            'limit' => 60,
            'period' => 60,
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());

        $rateLimitInfo = $rateLimitHandler->getRateLimitInfo();
        $this->assertSame(60, $rateLimitInfo['limit']);
        $this->assertSame(49, $rateLimitInfo['remaining']);

        $this->assertFalse($rateLimitHandler->isRateLimitExceeded());
    }
}
