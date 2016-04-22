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
use Cawa\Date\Date;
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\User;
use OAuth\OAuth2\Service\Facebook as FacebookService;

class Facebook extends AbstractProvider
{
    use HttpFactory;

    const DEFAULT_SCOPES = [
        FacebookService::SCOPE_EMAIL,
        FacebookService::SCOPE_USER_ABOUT,
    ];

    /**
     * @var FacebookService
     */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function getUser() : User
    {
        $code = $this->request()->getQuery('code');
        $state = $this->request()->getQuery('state');

        if (!$code) {
            throw new \LogicException('No code found on oauth route end');
        }

        // This was a callback request from facebook, get the token
        $token = $this->service->requestAccessToken($code, $state);

        // Send a request with it
        $result = json_decode($this->service->request('/me'), true);

        $gender = $this->pop($result, 'gender');
        $birthday = $this->pop($result, 'birthday');
        if ($birthday) {
            $explode = explode('/', $birthday);
            if (sizeof($explode) == 3) {
                $birthday = new Date($explode[2] . '-' . $explode[0] . '-' . $explode[1]);
            } elseif (sizeof($explode) == 2) {
                $birthday = new Date('0000-' . $explode[0] . '-' . $explode[1]);
            } else {
                $birthday = new Date($explode[0] . '-00-00');
            }
        }

        $user = new User($this->getType());
        $user->setUid($this->pop($result, 'id'))
            ->setEmail($this->pop($result, 'email'))
            ->setUsername($this->pop($result, 'username'))
            ->setVerified($this->pop($result, 'verified'))
            ->setFirstName($this->pop($result, 'first_name'))
            ->setLastName($this->pop($result, 'last_name'))
            ->setLocale(substr($this->pop($result, 'locale'), 0, 2))
            ->setGender($gender == 'male' ? User::GENDER_MALE : ($gender == 'female' ? User::GENDER_FEMALE : null))
            ->setBirthday($birthday)
            ->setExtraData($result)
        ;

        return $user;
    }
}
