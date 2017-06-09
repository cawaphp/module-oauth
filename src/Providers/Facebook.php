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
use Cawa\Oauth\AbstractProvider;
use Cawa\Oauth\User;
use Cawa\Renderer\HtmlPage;
use OAuth\OAuth2\Service\Facebook as FacebookService;

class Facebook extends AbstractProvider
{
    use HttpFactory;

    const DEFAULT_SCOPES = [
        FacebookService::SCOPE_EMAIL,
        FacebookService::SCOPE_USER_ABOUT,
        FacebookService::SCOPE_USER_BIRTHDAY,
    ];

    const API_VERSION = '2.8';

    /**
     * @var FacebookService
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
        $result = json_decode($this->service->request(
            '/me?fields=id,name,birthday,verified,first_name,last_name,email,locale'
        ), true);

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
                    appId: '" . DI::config()->get('socials/' . $this->getType() . '/key') . "',
                    version: 'v" . self::API_VERSION . "', 
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
