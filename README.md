ApiRateLimitBundle
==================

[![license](https://img.shields.io/github/license/IndraGunawan/api-rate-limit-bundle.svg?style=flat-square)](https://github.com/IndraGunawan/api-rate-limit-bundle/blob/master/LICENSE.md)
[![Travis](https://img.shields.io/travis/IndraGunawan/api-rate-limit-bundle.svg?style=flat-square)](https://travis-ci.org/IndraGunawan/api-rate-limit-bundle)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/IndraGunawan/api-rate-limit-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/IndraGunawan/api-rate-limit-bundle/badges/coverage.png?b=master)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/IndraGunawan/api-rate-limit-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/IndraGunawan/api-rate-limit-bundle/badges/quality-score.png?b=master)

This bundle provide rate limits protection for [api-platform](https://api-platform.com/) resources.

Installation
------------

### Download the Bundle

Require the bundle with composer.
```bash
composer require indragunawan/api-rate-limit-bundle
```

### Enable the Bundle

Enable the bundle in the kernel:
```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Indragunawan\ApiRateLimitBundle\IndragunawanApiRateLimitBundle(),
        );

        // ...
    }
}
```

Configuration
-------------

### Configuration Reference

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

    # Limit the request per period per IP address
    throttle:
        limit: 60 # max attempts per period
        period: 60 # in seconds

    # Exception thrown when rate limit exceeded
    exception:
        status_code: 429
        message: 'API rate limit exceeded for %s.' # %s will be replace with client IP address
        custom_exception: null
```

### Disable on development
```yml
# app/config/config_dev.yml
indragunawan_api_rate_limit:
    enabled: false
```

Todo
----

* Rate limit per user ROLE

License
-------

This bundle is under the MIT license. See the complete [license](LICENSE.md)
