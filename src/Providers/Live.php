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
use Cawa\Date\Date;
use Cawa\HttpClient\HttpClientFactory;
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\User;
use League\OAuth2\Client\Provider\AbstractProvider as AbstractProviderBase;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft as BaseService;

class Live extends AbstractProvider
{
    use HttpFactory;
    use HttpClientFactory;

    protected $type = self::LIVE;

    const BASE_API_URL = 'https://apis.live.net/v5.0/';
    const DEFAULT_SCOPES = [
        'wl.basic',
        'wl.contacts_emails',
        'wl.signin',
        'wl.birthday',
    ];

    /**
     * @var AbstractProviderBase
     */
    private $service;

    /**
     * @return BaseService
     */
    public function getService() : AbstractProviderBase
    {
        if (!$this->service) {
            $this->service = new BaseService(array_merge($this->getOptions(), [
                'clientId'     => $this->getKey(),
                'clientSecret' => $this->getSecret(),
                'redirectUri'  => $this->getRedirectUrl(),
            ]));

            $this->service->setHttpClient(self::guzzle(self::class));
        }

        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser() : User
    {
        $code = self::request()->getQuery('code');

        if (!$code) {
            throw new \LogicException('No code found on oauth route end');
        }

        // token
        $token = $this->getService()->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        // user
        $request = $this->getService()->getResourceOwner($token);
        $result = $request->toArray();

        $day = $this->pop($result, 'birth_day') ?? '00';
        $month = $this->pop($result, 'birth_month') ?? '00';
        $year = $this->pop($result, 'birth_year') ?? '0000';
        $birthday = $year . '-' . $month . '-' . $day;

        if ($birthday !== '0000-00-00') {
            $birthday = new Date($birthday);
        } else {
            $birthday = null;
        }

        $user = (new User($this->getType(), $token))
            ->setUid($this->pop($result, 'id'))
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
