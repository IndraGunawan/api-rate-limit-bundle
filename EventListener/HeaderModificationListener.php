<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @author Indra Gunawan <hello@indra.my.id>
 */
class HeaderModificationListener
{
    /**
     * @var array
     */
    private $header;

    public function __construct(array $header)
    {
        $this->header = $header;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (false === $this->header['display']) {
            return;
        }

        $request = $event->getRequest();
        $rateLimitInfo = $request->attributes->get('_api_rate_limit_info', null);
        if (null === $rateLimitInfo) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set($this->header['names']['limit'], $rateLimitInfo['limit'] ?? 0);
        $response->headers->set($this->header['names']['remaining'], $rateLimitInfo['remaining'] ?? 0);
        $response->headers->set($this->header['names']['reset'], $rateLimitInfo['reset'] ?? 0);
    }
}
