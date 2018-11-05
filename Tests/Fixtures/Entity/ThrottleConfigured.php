<?php

namespace Indragunawan\ApiRateLimitBundle\Tests\Fixtures\Entity;

use Indragunawan\ApiRateLimitBundle\Annotation\ApiRateLimit;

/**
 * This is a dummy entity. Remove it!
 *
 * @ApiRateLimit(
 *     enabled=true,
 *     throttle={
 *         "default"={
 *             "limit"=8,
 *             "period"=8
 *         },
 *         "roles"={
 *             "ROLE_USER"={
 *                 "limit"=4,
 *                 "period"=4
 *             }
 *         }
 *     }
 * )
 */
class ThrottleConfigured
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