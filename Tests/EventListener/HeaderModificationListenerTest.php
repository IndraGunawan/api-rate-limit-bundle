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

use Indragunawan\ApiRateLimitBundle\EventListener\HeaderModificationListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class HeaderModificationListenerTest extends TestCase
{
    public function testNotDisplayHeader()
    {
        $event = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())
            ->method('getRequest');

        $headerConfig = [
            'display' => false,
        ];

        $listener = new HeaderModificationListener($headerConfig);
        $listener->onKernelResponse($event);
    }

    public function testNoRateLimitInfoAttributes()
    {
        $event = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resetTime = gmdate('U');

        $request = Request::create('/api/me');
        $request->attributes->set('_api_rate_limit_info', null);

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $event->expects($this->never())
            ->method('getResponse');

        $headerConfig = [
            'display' => true,
        ];

        $listener = new HeaderModificationListener($headerConfig);
        $listener->onKernelResponse($event);
    }

    public function testSetResponseHeaders()
    {
        $event = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resetTime = gmdate('U');

        $request = Request::create('/api/me');
        $request->attributes->set('_api_rate_limit_info', [
            'limit' => 60,
            'remaining' => 59,
            'reset' => $resetTime,
        ]);

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $response = new Response();
        $event->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $headerConfig = [
            'display' => true,
            'names' => [
                'limit' => 'X-RateLimit-Limit',
                'remaining' => 'X-RateLimit-Remaining',
                'reset' => 'X-RateLimit-Reset',
            ],
        ];

        $listener = new HeaderModificationListener($headerConfig);
        $listener->onKernelResponse($event);

        $this->assertSame(60, $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame(59, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertSame($resetTime, $response->headers->get('X-RateLimit-Reset'));
    }
}
