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
final class ApiRateLimit
{
    /**
     * @var bool
     */
    public $enabled = true;
}
