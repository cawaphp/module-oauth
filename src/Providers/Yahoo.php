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
use Cawa\Oauth\Exceptions\Denied;
use Cawa\Oauth\Module;
use Cawa\Oauth\User;
use Cawa\Session\SessionFactory;
use League\OAuth2\Client\Provider\AbstractProvider as AbstractProviderBase;
use League\OAuth2\Client\Provider\GenericProvider;

class Yahoo extends AbstractProvider
{
    use HttpFactory;
    use HttpClientFactory;
    use SessionFactory;

    protected $type = self::TYPE_YAHOO;

    const BASE_API_URL = 'https://social.yahooapis.com/v1';
    const DEFAULT_SCOPES = [
        'openid',
    ];

    /**
     * @var AbstractProviderBase
     */
    private $service;

    /**
     * @return GenericProvider
     */
    public function getService() : AbstractProviderBase
    {
        if (!$this->service) {
            $this->service = new GenericProvider(array_merge($this->getOptions(), [
                'clientId' => $this->getKey(),
                'clientSecret' => $this->getSecret(),
                'redirectUri' => $this->getRedirectUrl(),
                'scopes' => $this->getScopes(),
                'scopeSeparator' => ' ',
                'urlAuthorize' => 'https://api.login.yahoo.com/oauth2/request_auth',
                'urlAccessToken' => 'https://api.login.yahoo.com/oauth2/get_token',
                'urlResourceOwnerDetails' => 'http://brentertainment.com/oauth2/lockdin/resource'
            ]));

            $this->service->setHttpClient(self::guzzle(self::class));
        }

        return $this->service;
    }

    /**
     * @return string
     */
    public function getAuthorizationUri() : string
    {
        $state = bin2hex(random_bytes(32 / 2));
        self::session()->set(Module::SESSION_STATE, $state);

        return $this->getService()->getAuthorizationUrl([
            'state' => $state,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        $code = self::request()->getQuery('code');
        $error = self::request()->getQuery('error');

        if (!$code) {
            throw new \LogicException('No code found on oauth route end');
        }

        if ($error == 'access_denied') {
            return new Denied($this->getType(), sprintf("Error Code '%s'", $error));
        } elseif ($error) {
            throw new \RuntimeException(sprintf("Failed with error '%s'", $error));
        }

        // token
        $token = $this->getService()->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        $yahooGuid = $token->getValues()['xoauth_yahoo_guid'];

        $request = $this->getService()->getAuthenticatedRequest(
            GenericProvider::METHOD_GET,
            self::BASE_API_URL . '/user/' . $yahooGuid . '/profile?format=json',
            $token
        );
        $result = $this->getService()->getParsedResponse($request);
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
