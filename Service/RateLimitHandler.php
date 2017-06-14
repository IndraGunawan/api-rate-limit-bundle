<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\Cache;
use Indragunawan\ApiRateLimitBundle\Annotation\ApiRateLimit;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Indra Gunawan <hello@indra.my.id>
 */
class RateLimitHandler
{
    /**
     * @var Cache
     */
    private $storage;

    /**
     * @var array
     */
    private $throttleConfig;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $remaining;

    /**
     * @var int
     */
    private $reset;

    /**
     * @var
     */
    private $enabled = true;

    /**
     * @var bool
     */
    private $rateLimitExceeded = false;

    public function __construct(Cache $storage, array $throttleConfig)
    {
        $this->storage = $storage;
        $this->throttleConfig = $throttleConfig;
    }

    public function handle(Request $request)
    {
        $key = $this->getKey($request);
        $limit = $this->throttleConfig['limit'];
        $period = $this->throttleConfig['period'];

        $annotationReader = new AnnotationReader();
        $annotation = $annotationReader->getClassAnnotation(new \ReflectionClass($request->attributes->get('_api_resource_class')), ApiRateLimit::class);
        if (null !== $annotation) {
            $this->enabled = $annotation->enabled;
        }

        if ($this->enabled) {
            $this->decreaseRateLimitRemaining($key, $limit, $period);
        }
    }

    public function getRateLimitInfo(): array
    {
        return [
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'reset' => $this->reset,
        ];
    }

    protected function getKey(Request $request): string
    {
        return sprintf('api_rate_limit$%s', sha1($request->getClientIp()));
    }

    protected function decreaseRateLimitRemaining(string $key, int $limit, int $period, int $cost = 1)
    {
        $currentTime = gmdate('U');
        $rateLimit = $this->storage->fetch($key);
        if (false !== $rateLimit && $currentTime <= $rateLimit['reset']) {
            // decrease existing rate limit remaining
            if ($rateLimit['remaining'] - $cost >= 0) {
                $remaining = $rateLimit['remaining'] - $cost;
                $reset = $rateLimit['reset'];
                $ttl = $rateLimit['reset'] - $currentTime;
            } else {
                $this->rateLimitExceeded = true;
                $this->reset = $rateLimit['reset'];
                $this->limit = $limit;
                $this->remaining = 0;

                return;
            }
        } else {
            // add / reset new rate limit remaining
            $remaining = $limit - $cost;
            $reset = $currentTime + $period;
            $ttl = $period;
        }

        $rateLimit = [
            'limit' => $limit,
            'remaining' => $remaining,
            'reset' => $reset,
        ];

        $this->storage->save($key, $rateLimit, $ttl);

        $this->limit = $limit;
        $this->remaining = $remaining;
        $this->reset = $reset;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function isRateLimitExceeded()
    {
        return $this->rateLimitExceeded;
    }
}
