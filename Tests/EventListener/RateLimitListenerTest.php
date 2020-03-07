<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\EventListener;

use Indragunawan\ApiRateLimitBundle\EventListener\RateLimitListener;
use Indragunawan\ApiRateLimitBundle\Service\RateLimitHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class RateLimitListenerTest extends TestCase
{
    public function testDisabledApiRateLimit()
    {
        $rateLimitHandler = $this->createMock(RateLimitHandler::class);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->never())
            ->method('isMasterRequest')
            ->willReturn(true);

        $tokenStorage = $this->createMock(TokenStorage::class);

        $listener = new RateLimitListener(false, $rateLimitHandler, [], $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testNotMasterRequest()
    {
        $rateLimitHandler = $this->createMock(RateLimitHandler::class);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $event->expects($this->never())
            ->method('getRequest')
            ->willReturn(Request::create('/api/me'));

        $tokenStorage = $this->createMock(TokenStorage::class);

        $listener = new RateLimitListener(true, $rateLimitHandler, [], $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testNoApiResourceClass()
    {
        $rateLimitHandler = $this->createMock(RateLimitHandler::class);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $request = Request::create('/api/me');

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $tokenStorage = $this->createMock(TokenStorage::class);

        $listener = new RateLimitListener(true, $rateLimitHandler, [], $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testRateLimitHandlerDisabled()
    {
        $rateLimitHandler = $this->createMock(RateLimitHandler::class);

        $rateLimitHandler->expects($this->once())
            ->method('handle');

        $rateLimitHandler->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $rateLimitHandler->expects($this->never())
            ->method('isRateLimitExceeded');

        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', 'Foo');

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $tokenStorage = $this->createMock(TokenStorage::class);

        $listener = new RateLimitListener(true, $rateLimitHandler, [], $tokenStorage);
        $listener->onKernelRequest($event);

        $this->assertTrue(true);
    }

    public function testRateLimitExceededForAnonymousUser()
    {
        $this->expectException(\Indragunawan\ApiRateLimitBundle\Exception\RateLimitExceededException::class);
        $this->expectExceptionMessage('API rate limit exceeded for 127.0.0.1.');

        $rateLimitHandler = $this->createMock(RateLimitHandler::class);

        $rateLimitHandler->expects($this->once())
            ->method('handle');

        $rateLimitHandler->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $rateLimitHandler->expects($this->once())
            ->method('isRateLimitExceeded')
            ->willReturn(true);

        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', 'Foo');

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $exceptionConfig = [
            'status_code' => 429,
            'message' => 'API rate limit exceeded for %s.',
        ];

        $tokenStorage = $this->createMock(TokenStorage::class);

        $listener = new RateLimitListener(true, $rateLimitHandler, $exceptionConfig, $tokenStorage);
        $listener->onKernelRequest($event);

        $this->assertTrue(true);
    }

    public function testRateLimitExceededForAuthenticatedUser()
    {
        $this->expectException(\Indragunawan\ApiRateLimitBundle\Exception\RateLimitExceededException::class);
        $this->expectExceptionMessage('API rate limit exceeded for user.');

        $rateLimitHandler = $this->createMock(RateLimitHandler::class);

        $rateLimitHandler->expects($this->once())
            ->method('handle');

        $rateLimitHandler->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $rateLimitHandler->expects($this->once())
            ->method('isRateLimitExceeded')
            ->willReturn(true);

        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', 'Foo');

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $exceptionConfig = [
            'status_code' => 429,
            'message' => 'API rate limit exceeded for %s.',
        ];

        $user = $this->createMock(UserInterface::class);

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $listener = new RateLimitListener(true, $rateLimitHandler, $exceptionConfig, $tokenStorage);
        $listener->onKernelRequest($event);

        $this->assertTrue(true);
    }
}
