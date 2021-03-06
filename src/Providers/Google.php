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
use Cawa\HttpClient\HttpClientFactory;
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\User;
use League\OAuth2\Client\Provider\AbstractProvider as AbstractProviderBase;
use League\OAuth2\Client\Provider\Google as BaseService;

class Google extends AbstractProvider
{
    use HttpFactory;
    use HttpClientFactory;

    protected $type = self::TYPE_GOOGLE;

    const BASE_API_URL = 'https://www.googleapis.com/oauth2/v1';
    const DEFAULT_SCOPES = [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
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
    public function getUser()
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
        $request = $this->getService()->getAuthenticatedRequest(
            BaseService::METHOD_GET,
            self::BASE_API_URL . '/userinfo',
            $token
        );
        $result = $this->getService()->getParsedResponse($request);

        $gender = $this->pop($result, 'gender');

        $user = (new User($this->getType(), $token))
            ->setUid($this->pop($result, 'id'))
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
