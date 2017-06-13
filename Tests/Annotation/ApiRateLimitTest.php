<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\Annotation;

use Indragunawan\ApiRateLimitBundle\Annotation\ApiRateLimit;
use PHPUnit\Framework\TestCase;

class ApiRateLimitTest extends TestCase
{
    public function testAssignation()
    {
        $rateLimit = new ApiRateLimit();
        $rateLimit->enabled = false;

        $this->assertFalse($rateLimit->enabled);
    }
}
