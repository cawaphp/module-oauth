<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Cawa\Oauth\Exceptions;

class InvalidState extends AbstractException
{
    /**
     * @param string $provider
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(string $provider, $code = 0, \Exception $previous = null)
    {
        parent::__construct(
            $provider,
            null,
            "Invalid state for service '" . $provider . "'",
            $code,
            $previous
        );
    }
}
