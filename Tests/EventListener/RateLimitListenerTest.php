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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RateLimitListenerTest extends TestCase
{
    public function testDisabledApiRateLimit()
    {
        $rateLimitHandler = $this->getMockBuilder(RateLimitHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())
            ->method('isMasterRequest')
            ->will($this->returnValue(true));

        $listener = new RateLimitListener(false, $rateLimitHandler, []);
        $listener->onKernelRequest($event);
    }

    public function testNotMasterRequest()
    {
        $rateLimitHandler = $this->getMockBuilder(RateLimitHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->will($this->returnValue(false));

        $event->expects($this->never())
            ->method('getRequest')
            ->will($this->returnValue(Request::create('/api/me')));

        $listener = new RateLimitListener(true, $rateLimitHandler, []);
        $listener->onKernelRequest($event);
    }

    public function testNoApiResourceClass()
    {
        $rateLimitHandler = $this->getMockBuilder(RateLimitHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        // $rateLimitHandler->expects($this->never())
        //     ->method('handle')
        //     ->willReturn($this->returnValue(true));

        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->will($this->returnValue(true));

        $request = Request::create('/api/me');

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $listener = new RateLimitListener(true, $rateLimitHandler, []);
        $listener->onKernelRequest($event);
    }

    public function testRateLimitHandlerDisabled()
    {
        $rateLimitHandler = $this->getMockBuilder(RateLimitHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rateLimitHandler->expects($this->once())
            ->method('handle');

        $rateLimitHandler->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        $rateLimitHandler->expects($this->never())
            ->method('isRateLimitExceeded');

        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->will($this->returnValue(true));

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', 'Foo');

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $listener = new RateLimitListener(true, $rateLimitHandler, []);
        $listener->onKernelRequest($event);

        $this->assertTrue(true);
    }

    public function testRateLimitExceeded()
    {
        $this->expectException(\Indragunawan\ApiRateLimitBundle\Exception\RateLimitExceededException::class);

        $rateLimitHandler = $this->getMockBuilder(RateLimitHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rateLimitHandler->expects($this->once())
            ->method('handle');

        $rateLimitHandler->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $rateLimitHandler->expects($this->once())
            ->method('isRateLimitExceeded')
            ->will($this->returnValue(true));

        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->will($this->returnValue(true));

        $request = Request::create('/api/me');
        $request->attributes->set('_api_resource_class', 'Foo');

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $exceptionConfig = [
            'status_code' => 429,
            'message' => 'API rate limit exceeded.',
        ];

        $listener = new RateLimitListener(true, $rateLimitHandler, $exceptionConfig);
        $listener->onKernelRequest($event);

        $this->assertTrue(true);
    }
}
