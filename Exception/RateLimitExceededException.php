<?php

/*
 * This file is part of the ApiRateLimitBundle
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author Indra Gunawan <hello@indra.my.id>
 */
class RateLimitExceededException extends HttpException
{
    public function __construct(int $statusCode, string $message, string $ip, string $username = null)
    {
        $message = sprintf($message, $username ?: $ip);

        parent::__construct($statusCode, $message);
    }
}
