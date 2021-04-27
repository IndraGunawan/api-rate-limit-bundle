CHANGELOG
=========

v0.5.0
------

* Allow PHP8, switch to Github action (#18)
* Enable rate limiting based on API Operations annotations (#17)

v0.4.0
------

* Symfony 5 compatibility (#14)

v0.3.0
------

* Feature. Configuration per using annotations (#9)
* Feature. Rate limit to specific methods (#12)
* Fix null ApiRateLimit instance in RateLimitHandler (#11)

v0.2.1
------

* Adding Symfony4 support (#5)
* Fix dependencies for Symfony Flex

v0.2.0
------

* Feature. Using PSR/Cache instead of DoctrineCache (#2)
* Feature. Role based rate limit (#3)
* Deprecated the `indragunawan_api_rate_limit.storage` configuration key.
* Deprecated the `indragunawan_api_rate_limit.throttle.period` and `indragunawan_api_rate_limit.throttle.limit` configuration key.

v0.1.0
------

* Initial release
