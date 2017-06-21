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
