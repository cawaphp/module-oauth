<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Cawa\Oauth\Exceptions;

class AbstractException extends \Exception
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @return string
     */
    public function getKey() : string
    {
        return $this->key;
    }

    /**
     * @var string
     */
    protected $provider;

    /**
     * @return string
     */
    public function getProvider() : string
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     * @param string $key
     * @param int $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(string $provider, string $key, $message, $code = 0, \Exception $previous = null)
    {
        $this->provider = $provider;
        $this->key = $key;

        parent::__construct($message, $code, $previous);
    }
}
