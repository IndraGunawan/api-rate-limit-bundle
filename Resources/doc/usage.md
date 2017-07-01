Usage
=====

Disable on development
----------------------

By default, rate limit applies to all environments. If you wish to develop/test without worrying about exceeding the rate limit, you can set the `enabled` configuration to `false` to disable the rate limit.

```yml
# app/config/config_dev.yml

indragunawan_api_rate_limit:
    enabled: false
```

---

Disable rate limit per resource
-------------------------------

By default, rate limit applies to all ApiResources. If you wish to disable the rate limit on some resources, you can use the `ApiRateLimit` annotation and set the `enabled` property to `false` to disable the rate limit.

```php
<?php

// src/AppBundle/Entity/Foo.php

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Indragunawan\ApiRateLimitBundle\Annotation\ApiRateLimit;

/**
 * @ApiResource
 * @ORM\Entity
 * @ApiRateLimit(enabled=false)
 */
class Foo
{
    // ...
}
```

---

Custom Exception
----------------

You can use `indragunawan_api_rate_limit.exception.status_code` and `indragunawan_api_rate_limit.exception.message` configuration keys to set HTTP status code and exception message. But if you want to throw custom exception class when user reaches the rate limit, you can create your own exception class that must extend `Indragunawan\ApiRateLimitBundle\Exception\RateLimitExceededException` class and set the class namespace to `indragunawan_api_rate_limit.exception.custom_exception` configuration.

```yml
# app/config/config.yml

indragunawan_api_rate_limit:
    exception:
        custom_exception: "Custom\\Exception\\Class"
```

---

Custom Cache
------------

This bundle implements [PSR-6: Caching Interface](http://www.php-fig.org/psr/psr-6/) standard to persist rate limit metadata. By default, if no custom cache service provided, this bundle use FilesystemAdapter (location: %kernel.cache_dir%/api_rate_limit).

If you want to use custom cache, you can create a service of cache adapter that is supported by [symfony/cache](https://symfony.com/doc/current/components/cache/cache_pools.html#creating-cache-pools). Never set the `$defaultLifetime` argument of the adapter class.

Here is an example of using Redis as api rate limit cache.

Creating cache service.
```yml
# Symfony >= 3.1
# app/config/config.yml

framework:
    cache:
        pools:
            cache.api_rate_limit:
                adapter: cache.adapter.redis
                provider: redis_client_service # use SncRedisBundle or create your own redis client service
```

```yml
# Symfony <= 3.0
# app/config/services.yml

services:
    cache.api_rate_limit:
        class: "Symfony\\Component\\Cache\\Adapter\\RedisAdapter"
        arguments: ["@redis_client_service"] # use SncRedisBundle or create your own redis client service
```

assign cache service.
```yml
# app/config/config.yml

indragunawan_api_rate_limit:
    cache: cache.api_rate_limit
```

---

[Return to the index.](../../README.md)
