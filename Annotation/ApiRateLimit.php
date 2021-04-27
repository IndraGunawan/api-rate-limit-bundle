<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 *
 * @author Indra Gunawan <hello@indra.my.id>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApiRateLimit
{
    public function __construct($enabled = true, array $throttle = [], array $methods = [])
    {
        if (\is_array($enabled)) {
            // supports doctrine annotations
            foreach ($enabled as $key => $value) {
                $this->$key = $value;
            }
        } else {
            $this->enabled = $enabled;
            $this->throttle = $throttle;
            $this->methods = $methods;
        }
    }

    /**
     * @var bool
     */
    public $enabled = true;

    /**
     * @var array
     */
    public $throttle = [];

    /**
     * @var array
     *
     * @example ["GET", "POST"]
     */
    public $methods = [];
}
