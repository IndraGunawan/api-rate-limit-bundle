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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HeaderModificationListenerTest extends TestCase
{
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    public function tearDown(): void
    {
        $this->kernel = null;
    }

    public function testNotDisplayHeader()
    {
        $responseHeaderBag = $this->prophesize(ResponseHeaderBag::class);
        $responseHeaderBag->set()->shouldNotHaveBeenCalled();

        $response = $this->prophesize(Response::class);
        $response->headers = $responseHeaderBag->reveal();

        $event = new ResponseEvent($this->kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $response->reveal());

        $headerConfig = [
            'display' => false,
        ];

        $listener = new HeaderModificationListener($headerConfig);
        $listener->onKernelResponse($event);
    }

    public function testNoRateLimitInfoAttributes()
    {
        $attributes = $this->prophesize(ParameterBag::class);
        $attributes->get('_api_rate_limit_info', null)->willReturn(null)->shouldBeCalledTimes(1);

        $request = $this->prophesize(Request::class);
        $request->attributes = $attributes->reveal();

        $event = new ResponseEvent($this->kernel, $request->reveal(), HttpKernelInterface::MASTER_REQUEST, new Response());

        $headerConfig = [
            'display' => true,
        ];

        $listener = new HeaderModificationListener($headerConfig);
        $listener->onKernelResponse($event);
    }

    public function testSetResponseHeaders()
    {
        $resetTime = gmdate('U');

        $attributes = $this->prophesize(ParameterBag::class);
        $attributes->get('_api_rate_limit_info', null)->willReturn([
            'limit' => 60,
            'remaining' => 59,
            'reset' => $resetTime,
        ])->shouldBeCalledTimes(1);

        $request = $this->prophesize(Request::class);
        $request->attributes = $attributes->reveal();

        $response = new Response();

        $event = new ResponseEvent($this->kernel, $request->reveal(), HttpKernelInterface::MASTER_REQUEST, $response);

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

        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(59, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertSame($resetTime, $response->headers->get('X-RateLimit-Reset'));
    }
}
