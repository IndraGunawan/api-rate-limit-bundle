Installation
============

Download the Bundle
-------------------

Require the bundle with composer. (`indragunawan/api-rate-limit-bundle` on [Packagist](https://packagist.org/packages/indragunawan/api-rate-limit-bundle));
```bash
composer require indragunawan/api-rate-limit-bundle
```

Enable the Bundle
-----------------

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

---

[Return to the index.](../../README.md)
