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
use Cawa\Core\DI;
use Cawa\Date\Date;
use Cawa\HttpClient\HttpClientFactory;
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\User;
use Cawa\Renderer\HtmlPage;
use League\OAuth2\Client\Provider\AbstractProvider as AbstractProviderBase;
use League\OAuth2\Client\Provider\AppSecretProof;
use League\OAuth2\Client\Provider\Facebook as BaseService;

class Facebook extends AbstractProvider
{
    use HttpFactory;
    use HttpClientFactory;

    protected $type = self::TYPE_FACEBOOK;

    const BASE_GRAPH_URL = 'https://graph.facebook.com';
    const API_VERSION = 'v2.11';
    const DEFAULT_SCOPES = [
        'email',
        'user_about_me',
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
                'graphApiVersion' => self::API_VERSION,
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
        $fields = [
            'id', 'name', 'first_name', 'last_name', 'verified',
            'email', 'hometown', 'picture.type(large){url,is_silhouette}',
            'cover{source}', 'gender', 'locale', 'link', 'timezone', 'age_range', 'birthday'
        ];
        $appSecretProof = AppSecretProof::create($this->getSecret(), $token->getToken());
        $url = self::BASE_GRAPH_URL . '/' . self::API_VERSION . '/me?fields=' . implode(',', $fields) .
            '&access_token=' . $token . '&appsecret_proof=' . $appSecretProof;

        $request = $this->getService()->getAuthenticatedRequest(
            BaseService::METHOD_GET,
            $url,
            $token
        );
        $result = $this->getService()->getParsedResponse($request);

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

        $user = (new User($this->getType(), $token))
            ->setUid($this->pop($result, 'id'))
            ->setEmail($this->pop($result, 'email'))
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

    /**
     * @param string $redirect
     *
     * @return HtmlPage
     */
    public function getClientMasterpage(string $redirect) : HtmlPage
    {
        $masterpage = (new HtmlPage());
        $masterpage->setHeadTitle('Facebook Connect');
        $masterpage->addJs('//connect.facebook.net/en_US/sdk.js');
        $masterpage->addJs("
            window.fbAsyncInit = function ()
            {
                FB.init({
                    appId: '" . DI::config()->get('socials/' . strtolower($this->getType()) . '/key') . "',
                    version: 'v2.8', 
                    cookie: true
                });
            
                FB.getLoginStatus(function (response)
                {
                    if (console && console.log) {
                        console.log(response.status);
                    }

                    if (response.status === 'connected') {
                        window.location.href = '/oauth/facebook/start'
                    } else if (response.status === 'not_authorized') {
                        window.location.href = " . json_encode($redirect) . '
                    } else {
                        window.location.href = ' . json_encode($redirect) . '
                    }
                });
            };
        ');

        return $masterpage;
    }
}
