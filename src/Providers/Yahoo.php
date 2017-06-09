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
use Cawa\Oauth\Exceptions\Denied;
use Cawa\Oauth\User;
use OAuth\OAuth2\Service\Yahoo as YahooService;

class Yahoo extends AbstractProvider
{
    use HttpFactory;

    const DEFAULT_SCOPES = [
        YahooService::SCOPE_OPENID,
    ];

    /**
     * @var YahooService
     */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        $code = self::request()->getQuery('code');
        $state = self::request()->getQuery('state');

        $error = self::request()->getQuery('error');

        if ($error == 'access_denied') {
            return new Denied($this->getType(), $error, sprintf("Error Code '%s'", $error));
        } elseif ($error) {
            throw new \Exception(sprintf("Failed with error '%s'", $error));
        }

        if (!$code) {
            throw new \LogicException('No code found on oauth route end');
        }

        // This was a callback request from google, get the token
        $token = $this->service->requestAccessToken($code, $state);
        $yahooGuid = $token->getExtraParams()['xoauth_yahoo_guid'];

        $url = 'https://social.yahooapis.com/v1/user/' . $yahooGuid . '/profile?format=json';

        // Send a request with it
        $result = json_decode($this->service->request($url), true);
        $result = $result['profile'];

        $email = $result['emails'][0]['handle'];
        $gender = $this->pop($result, 'gender');

        $user = new User($this->getType());
        $user->setUid($this->pop($result, 'guid'))
            ->setEmail($email)
            ->setVerified($email ? true : false)
            ->setFirstName($this->pop($result, 'givenName'))
            ->setLastName($this->pop($result, 'familyName'))
            ->setGender($gender == 'M' ? User::GENDER_MALE : ($gender == 'F' ? User::GENDER_FEMALE : null))
            ->setLocale($this->pop($result, 'lang'))
            ->setExtraData($result)
        ;

        return $user;
    }
}
