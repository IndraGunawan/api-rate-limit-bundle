Configuration
=============

Configuration Reference
-----------------------

Default bundle configuration
```yml
indragunawan_api_rate_limit:
    enabled: true

    # The service that is used to persist rate limit metadata. The service has to implement the
    # Psr\Cache\CacheItemPoolInterface interface. If no service id provided then the default cache
    # is Filesystem (location: %kernel.cache_dir%/api_rate_limit).
    cache: ~

    # Response header for rate limit information
    header:
        display: true
        names:
            limit: X-RateLimit-Limit
            remaining: X-RateLimit-Remaining
            reset: X-RateLimit-Reset

    # Limit the request per period per IP address / user
    throttle:
        default:
            limit: 60 # max attempts per period
            period: 60 # in seconds
        roles: {}
        sort: 'rate-limit-desc' # available value 'first-match', 'rate-limit-asc', 'rate-limit-desc'. default value rate-limit-desc

    # Exception thrown when rate limit exceeded
    exception:
        status_code: 429
        message: 'API rate limit exceeded for %s.' # %s will be replace with client IP address
        custom_exception: ~ # The exception has to extend Indragunawan\ApiRateLimitBundle\Exception\RateLimitExceededException
```

---

[Return to the index.](../../README.md)
