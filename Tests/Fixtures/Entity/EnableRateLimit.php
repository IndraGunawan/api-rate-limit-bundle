<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\Fixtures\Entity;

use Indragunawan\ApiRateLimitBundle\Annotation\ApiRateLimit;

/**
 * This is a dummy entity. Remove it!
 *
 * @ApiRateLimit(enabled=true)
 */
class EnableRateLimit
{
    /**
     * @var int The entity Id
     */
    private $id;

    /**
     * @var string Something else
     */
    private $bar = '';

    public function getId()
    {
        return $this->id;
    }

    public function getBar(): string
    {
        return $this->bar;
    }

    public function setBar(string $bar)
    {
        $this->bar = $bar;
    }
}
