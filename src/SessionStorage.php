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

namespace Cawa\Oauth;

use Cawa\Session\SessionFactory;
use OAuth\Common\Storage\Exception\AuthorizationStateNotFoundException;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\TokenInterface;

class SessionStorage implements TokenStorageInterface
{
    use SessionFactory;

    //region Constants

    /**
     * Access Token.
     */
    const SESSION_VAR_TOKEN = 'OAUTH_TOKEN';

    /**
     * State.
     */
    const SESSION_VAR_STATE = 'OAUTH_STATE';

    //endregion

    /**
     * {@inheritdoc}
     */
    public function retrieveAccessToken($service)
    {
        if ($this->hasAccessToken($service)) {
            // get from session
            $tokens = self::session()->get(self::SESSION_VAR_TOKEN);

            // one item
            return $tokens[$service];
        }

        throw new TokenNotFoundException('Token not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritdoc}
     */
    public function storeAccessToken($service, TokenInterface $token)
    {
        // get previously saved tokens
        $tokens = self::session()->get(self::SESSION_VAR_TOKEN);

        if (!is_array($tokens)) {
            $tokens = [];
        }

        $tokens[$service] = $token;

        // save
        self::session()->set(self::SESSION_VAR_TOKEN, $tokens);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccessToken($service)
    {
        // get from session
        $tokens = self::session()->get(self::SESSION_VAR_TOKEN);

        return is_array($tokens) &&
            isset($tokens[$service]) &&
            $tokens[$service] instanceof TokenInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken($service)
    {
        // get previously saved tokens
        $tokens = self::session()->get(self::SESSION_VAR_TOKEN);

        if (is_array($tokens) && array_key_exists($service, $tokens)) {
            unset($tokens[$service]);

            // Replace the stored tokens array
            self::session()->set(self::SESSION_VAR_TOKEN, $tokens);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllTokens()
    {
        self::session()->remove(self::SESSION_VAR_TOKEN);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveAuthorizationState($service)
    {
        if ($this->hasAuthorizationState($service)) {
            // get from session
            $states = self::session()->get(self::SESSION_VAR_STATE);

            // one item
            return $states[$service];
        }

        throw new AuthorizationStateNotFoundException('State not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritdoc}
     */
    public function storeAuthorizationState($service, $state)
    {
        // get previously saved tokens
        $states = self::session()->get(self::SESSION_VAR_STATE);

        if (!is_array($states)) {
            $states = [];
        }

        $states[$service] = $state;

        // save
        self::session()->set(self::SESSION_VAR_STATE, $states);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAuthorizationState($service)
    {
        // get from session
        $states = self::session()->get(self::SESSION_VAR_STATE);

        return is_array($states) &&
            isset($states[$service]) &&
            $states[$service] !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearAuthorizationState($service)
    {
        // get previously saved tokens
        $states = self::session()->get(self::SESSION_VAR_STATE);

        if (is_array($states) && array_key_exists($service, $states)) {
            unset($states[$service]);

            // Replace the stored tokens array
            self::session()->set(self::SESSION_VAR_STATE, $states);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllAuthorizationStates()
    {
        self::session()->remove(self::SESSION_VAR_STATE);

        return $this;
    }
}
