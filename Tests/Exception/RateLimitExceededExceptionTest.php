<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\Exception;

use Indragunawan\ApiRateLimitBundle\Exception\RateLimitExceededException;
use PHPUnit\Framework\TestCase;

class RateLimitExceededExceptionTest extends TestCase
{
    public function testExceptionMessage()
    {
        $exception = new RateLimitExceededException(429, 'API rate limit exceeded for %s.', '127.0.0.1');

        $this->assertSame('API rate limit exceeded for 127.0.0.1.', $exception->getMessage());
    }
}
