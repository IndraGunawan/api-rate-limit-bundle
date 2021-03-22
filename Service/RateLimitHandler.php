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
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @author Indra Gunawan <hello@indra.my.id>
 */
class RateLimitHandler
{
    /**
     * @var CacheItemPoolInterface
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

    /**
     * RateLimitHandler constructor.
     */
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

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
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

    public static function generateCacheKey(string $ip, string $username = null, string $userRole = null): string
    {
        if (!empty($username) && !empty($userRole)) {
            return sprintf('_api_rate_limit_metadata$%s', sha1($userRole.$username));
        }

        return sprintf('_api_rate_limit_metadata$%s', sha1($ip));
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
     public function handle(Request $request)
    {
        $annotationReader = new AnnotationReader();
        /** @var ApiRateLimit $annotation */
        $annotation = $annotationReader->getClassAnnotation(
            new ReflectionClass($request->attributes->get('_api_resource_class')),
            ApiRateLimit::class
        );

        if (null !== $annotation) {
            $this->enabled = $annotation->enabled;
            if ((
                    !\in_array($request->getMethod(), array_map('strtoupper', $annotation->methods), true)
                    &&
                    !\in_array($request->attributes->get('_api_item_operation_name'),array_map('strtolower', $annotation->methods),true)
                )
                && !empty($annotation->methods)
            ) {
                // The annotation is ignored as the method is not corresponding
                $annotation = new ApiRateLimit();
            }
        } else {
            $annotation = new ApiRateLimit();
        }

        list($key, $limit, $period) = $this->getThrottle($request, $annotation);

        if ($this->enabled) {
            $this->decreaseRateLimitRemaining($key, $limit, $period);
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
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

    /**
     * @return array
     */
    private function getThrottle(Request $request, ApiRateLimit $annotation)
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            // no anonymous
            if (\is_object($token->getUser())) {
                $rolesConfig = $this->throttleConfig['roles'];
                if (!empty($annotation->throttle['roles'])) {
                    $rolesConfig = $annotation->throttle['roles'];
                }

                foreach ($rolesConfig as $role => $throttle) {
                    if ($this->authorizationChecker->isGranted($role)) {
                        $username = $token->getUsername();
                        $userRole = $role;
                        $limit = $throttle['limit'];
                        $period = $throttle['period'];

                        return [self::generateCacheKey($request->getClientIp(), $username, $userRole), $limit, $period];
                    }
                }
            }
        }

        if (!empty($annotation->throttle['default'])) {
            $limit = $annotation->throttle['default']['limit'];
            $period = $annotation->throttle['default']['period'];
        } else {
            $limit = $this->throttleConfig['default']['limit'];
            $period = $this->throttleConfig['default']['period'];
        }

        return [self::generateCacheKey($request->getClientIp()), $limit, $period];
    }
}
