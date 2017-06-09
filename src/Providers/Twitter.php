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

namespace Cawa\Oauth\Providers;

use Cawa\App\HttpFactory;
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\User;
use OAuth\OAuth1\Service\Twitter as TwitterService;

class Twitter extends AbstractProvider
{
    use HttpFactory;

    /**
     * @var TwitterService
     */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function getUser() : User
    {
        $oauthToken = self::request()->getQuery('oauth_token');
        $oauthVerifier = self::request()->getQuery('oauth_verifier');

        if (!$oauthToken || !$oauthVerifier) {
            throw new \LogicException('No code found on oauth route end');
        }

        $token = $this->service->getStorage()->retrieveAccessToken('Twitter');

        // This was a callback request from twitter, get the token
        $this->service->requestAccessToken(
            $oauthToken,
            $oauthVerifier,
            $token->getRequestTokenSecret()
        );
        // Send a request now that we have access token
        $result = json_decode($this->service->request('account/verify_credentials.json?include_email=true'), true);

        $name = $this->pop($result, 'name');
        $firstname = $lastname = null;
        if ($name) {
            $explode = explode(' ', $name);
            if (isset($explode[0])) {
                $firstname = array_shift($explode);
                $lastname = implode(' ', $explode);
            }
        }

        $user = new User($this->getType());
        $user->setUid($this->pop($result, 'id_str'))
            ->setEmail($this->pop($result, 'email'))
            ->setUsername($this->pop($result, 'screen_name'))
            ->setVerified($this->pop($result, 'verified'))
            ->setFirstName($firstname)
            ->setLastName($lastname)
            ->setLocale($this->pop($result, 'lang'))
            ->setExtraData($result)
        ;

        return $user;
    }

    /**
     * @return string
     */
    public function getAuthorizationUri() : string
    {
        $token = $this->service->requestRequestToken();
        $url = $this->service->getAuthorizationUri(['oauth_token' => $token->getRequestToken()]);

        return $url->__toString();
    }
}
