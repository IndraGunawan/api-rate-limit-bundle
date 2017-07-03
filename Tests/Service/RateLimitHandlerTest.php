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
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\User\UserInterface;

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

        $tokenStorage = $this->createMock(TokenStorage::class);
        $authorizationChecker = $this->getMockBuilder(AuthorizationChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $throttleConfig = [
            'default' => [
                'limit' => 60,
                'period' => 60,
            ],
            'roles' => [],
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', DisableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $tokenStorage, $authorizationChecker, $throttleConfig);
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

        $tokenStorage = $this->createMock(TokenStorage::class);
        $authorizationChecker = $this->getMockBuilder(AuthorizationChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $throttleConfig = [
            'default' => [
                'limit' => 60,
                'period' => 60,
            ],
            'roles' => [],
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $tokenStorage, $authorizationChecker, $throttleConfig);
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
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(true));

        $cacheItem->expects($this->once())
            ->method('get')
            ->will($this->returnValue([
                'limit' => 60,
                'remaining' => 0,
                'reset' => gmdate('U'),
            ]));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $tokenStorage = $this->createMock(TokenStorage::class);
        $authorizationChecker = $this->getMockBuilder(AuthorizationChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $throttleConfig = [
            'default' => [
                'limit' => 60,
                'period' => 60,
            ],
            'roles' => [],
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $tokenStorage, $authorizationChecker, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());
        $rateLimitInfo = $rateLimitHandler->getRateLimitInfo();
        $this->assertSame(60, $rateLimitInfo['limit']);
        $this->assertSame(0, $rateLimitInfo['remaining']);
        $this->assertTrue($rateLimitHandler->isRateLimitExceeded());
    }

    public function testRateLimitIsNotExceeded()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(true));

        $cacheItem->expects($this->once())
            ->method('get')
            ->will($this->returnValue([
                'limit' => 60,
                'remaining' => 50,
                'reset' => gmdate('U'),
            ]));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $tokenStorage = $this->createMock(TokenStorage::class);
        $authorizationChecker = $this->getMockBuilder(AuthorizationChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $throttleConfig = [
            'default' => [
                'limit' => 60,
                'period' => 60,
            ],
            'roles' => [],
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $tokenStorage, $authorizationChecker, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());

        $rateLimitInfo = $rateLimitHandler->getRateLimitInfo();
        $this->assertSame(60, $rateLimitInfo['limit']);
        $this->assertSame(49, $rateLimitInfo['remaining']);

        $this->assertFalse($rateLimitHandler->isRateLimitExceeded());
    }

    public function testRateLimitByRole()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(false));

        $cacheItem->expects($this->once())
            ->method('get')
            ->will($this->returnValue([]));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $user = $this->createMock(UserInterface::class);

        $token = $this->getMockBuilder(UsernamePasswordToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $token->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('myusername'));

        $token->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue(true));

        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager->expects($this->once())
            ->method('decide')
            ->will($this->returnValue(true));

        $authorizationChecker = $this->getMockBuilder(AuthorizationChecker::class)
            ->setConstructorArgs([$tokenStorage, $authenticationManager, $accessDecisionManager])
            ->getMock();

        $throttleConfig = [
            'default' => [
                'limit' => 60,
                'period' => 60,
            ],
            'roles' => [
                'ROLE_USER' => [
                    'limit' => 10,
                    'period' => 10,
                ],
            ],
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $tokenStorage, $authorizationChecker, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());

        $rateLimitInfo = $rateLimitHandler->getRateLimitInfo();
        $this->assertSame(10, $rateLimitInfo['limit']);
        $this->assertSame(9, $rateLimitInfo['remaining']);

        $this->assertFalse($rateLimitHandler->isRateLimitExceeded());
    }

    public function testTokenNotFound()
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->will($this->returnValue(false));

        $cacheItem->expects($this->once())
            ->method('get')
            ->will($this->returnValue([]));

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($cacheItem));

        $token = $this->getMockBuilder(UsernamePasswordToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue(null));

        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);

        $authorizationChecker = $this->getMockBuilder(AuthorizationChecker::class)
            ->setConstructorArgs([$tokenStorage, $authenticationManager, $accessDecisionManager])
            ->getMock();

        $throttleConfig = [
            'default' => [
                'limit' => 60,
                'period' => 60,
            ],
            'roles' => [
                'ROLE_USER' => [
                    'limit' => 10,
                    'period' => 10,
                ],
            ],
        ];

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', EnableRateLimit::class);

        $rateLimitHandler = new RateLimitHandler($cacheItemPool, $tokenStorage, $authorizationChecker, $throttleConfig);
        $rateLimitHandler->handle($request);

        $this->assertTrue($rateLimitHandler->isEnabled());

        $rateLimitInfo = $rateLimitHandler->getRateLimitInfo();
        $this->assertSame(60, $rateLimitInfo['limit']);
        $this->assertSame(59, $rateLimitInfo['remaining']);

        $this->assertFalse($rateLimitHandler->isRateLimitExceeded());
    }
}
