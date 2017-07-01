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
use Indragunawan\ApiRateLimitBundle\Annotation\ApiRateLimit;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @author Indra Gunawan <hello@indra.my.id>
 */
class RateLimitHandler
{
    /**
     * @var Cache
     */
    private $cacheItemPool;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

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

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        array $throttleConfig
    ) {
        $this->cacheItemPool = $cacheItemPool;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->throttleConfig = $throttleConfig;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function isRateLimitExceeded()
    {
        return $this->rateLimitExceeded;
    }

    public function getRateLimitInfo(): array
    {
        return [
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'reset' => $this->reset,
        ];
    }

    public static function generateCacheKey(string $ip, string $userName = null, string $userRole = null): string
    {
        return sprintf('_api_rate_limit_metadata$%s', sha1($userName && $userRole ? sprintf('%s$%s', $userRole, $userName) : $ip));
    }

    public function handle(Request $request)
    {
        list($key, $limit, $period) = $this->getThrottle($request);

        $annotationReader = new AnnotationReader();
        $annotation = $annotationReader->getClassAnnotation(new \ReflectionClass($request->attributes->get('_api_resource_class')), ApiRateLimit::class);
        if (null !== $annotation) {
            $this->enabled = $annotation->enabled;
        }

        if ($this->enabled) {
            $this->decreaseRateLimitRemaining($key, $limit, $period);
        }
    }

    protected function decreaseRateLimitRemaining(string $key, int $limit, int $period)
    {
        $cost = 1;
        $currentTime = gmdate('U');

        $rateLimitInfo = $this->cacheItemPool->getItem($key);
        $rateLimit = $rateLimitInfo->get();
        if ($rateLimitInfo->isHit() && $currentTime <= $rateLimit['reset']) {
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

        $rateLimitInfo->set($rateLimit);
        $rateLimitInfo->expiresAfter($ttl);

        $this->cacheItemPool->save($rateLimitInfo);

        $this->limit = $limit;
        $this->remaining = $remaining;
        $this->reset = $reset;
    }

    private function getThrottle(Request $request)
    {
        $userName = null;
        $userRole = null;
        $limit = $this->throttleConfig['default']['limit'];
        $period = $this->throttleConfig['default']['period'];

        foreach ($this->throttleConfig['roles'] as $role => $throttle) {
            try {
                if ($this->authorizationChecker->isGranted($role)) {
                    $userName = $this->tokenStorage->getToken()->getUsername();
                    $userRole = $role;
                    $limit = $throttle['limit'];
                    $period = $throttle['period'];

                    break;
                }
            } catch (AuthenticationCredentialsNotFoundException $e) {
                // do nothing
            }
        }

        return [self::generateCacheKey($request->getClientIp(), $userName, $userRole), $limit, $period];
    }
}
