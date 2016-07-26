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
use OAuth\OAuth2\Service\Microsoft as MicrosoftService;

class Microsoft extends AbstractProvider
{
    use HttpFactory;

    const DEFAULT_SCOPES = [
        MicrosoftService::SCOPE_BASIC,
        MicrosoftService::SCOPE_CONTACTS_EMAILS,
        MicrosoftService::SCOPE_SIGNIN,
        MicrosoftService::SCOPE_BIRTHDAY,
    ];

    /**
     * @var Microsoft
     */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function getUser() : User
    {
        $code = self::request()->getQuery('code');
        $state = self::request()->getQuery('state');

        if (!$code) {
            throw new \LogicException('No code found on oauth route end');
        }

        // This was a callback request from facebook, get the token
        $token = $this->service->requestAccessToken($code, $state);

        // Send a request with it
        $result = json_decode($this->service->request('/me'), true);

        $day = $this->pop($result, 'birth_day') ?? '00';
        $month = $this->pop($result, 'birth_month') ?? '00';
        $year = $this->pop($result, 'birth_year') ?? '0000';
        $birthday = $year . '-' . $month . '-' . $day;

        if ($birthday !== '0000-00-00') {
            $birthday = new Date($birthday);
        } else {
            $birthday = null;
        }

        $user = new User($this->getType());
        $user->setUid($this->pop($result, 'id'))
            ->setEmail($this->pop($result, ['emails', 'preferred']))
            ->setGender($this->pop($result, 'gender'))
            ->setUsername($this->pop($result, 'name'))
            ->setFirstName($this->pop($result, 'first_name'))
            ->setLastName($this->pop($result, 'last_name'))
            ->setLocale($this->pop($result, 'locale'))
            ->setBirthday($birthday)
            ->setExtraData($result)
        ;

        return $user;
    }
}
