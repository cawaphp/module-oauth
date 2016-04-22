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

namespace Cawa\Oauth\Providers;

use Cawa\App\HttpFactory;
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\Exceptions\Denied;
use Cawa\Oauth\User;
use OAuth\OAuth2\Service\Google as GoogleService;

class Google extends AbstractProvider
{
    use HttpFactory;

    const DEFAULT_SCOPES = [
        GoogleService::SCOPE_USERINFO_EMAIL,
        GoogleService::SCOPE_USERINFO_PROFILE,
    ];

    /**
     * @var GoogleService
     */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        $code = $this->request()->getQuery('code');
        $state = $this->request()->getQuery('state');

        $error = $this->request()->getQuery('error');

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

        // Send a request with it
        $result = json_decode($this->service->request('userinfo'), true);

        $gender = $this->pop($result, 'gender');

        $user = new User($this->getType());
        $user->setUid($this->pop($result, 'id'))
            ->setEmail($this->pop($result, 'email'))
            ->setVerified($this->pop($result, 'verified_email'))
            ->setFirstName($this->pop($result, 'given_name'))
            ->setLastName($this->pop($result, 'family_name'))
            ->setGender($gender == 'male' ? User::GENDER_MALE : ($gender == 'female' ? User::GENDER_FEMALE : null))
            ->setLocale($this->pop($result, 'locale'))
            ->setExtraData($result)
        ;

        return $user;
    }
}
