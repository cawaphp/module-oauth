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
use Cawa\Oauth\Module;
use Cawa\Oauth\User;
use Cawa\Session\SessionFactory;
use League\OAuth2\Client\Provider\AbstractProvider as AbstractProviderBase;
use League\OAuth2\Client\Provider\GenericProvider;
use Microsoft\Graph\Graph;

class Microsoft extends AbstractProvider
{
    use HttpFactory;
    use HttpClientFactory;
    use SessionFactory;

    protected $type = self::TYPE_MICROSOFT;

    const AUTHORITY_URL = 'https://login.microsoftonline.com/common';
    const AUTHORIZE_ENDPOINT = '/oauth2/v2.0/authorize';
    const TOKEN_ENDPOINT = '/oauth2/v2.0/token';

    const DEFAULT_SCOPES = [
        'profile',
        'openid',
        'email',
        'User.Read',
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
            $this->service = new GenericProvider([
                'clientId' => $this->getKey(),
                'clientSecret' => $this->getSecret(),
                'redirectUri' => $this->getRedirectUrl(),
                'urlAuthorize' => self::AUTHORITY_URL . self::AUTHORIZE_ENDPOINT,
                'urlAccessToken' => self::AUTHORITY_URL . self::TOKEN_ENDPOINT,
                'urlResourceOwnerDetails' => '',
                'scopes' => $this->getScopes(),
                'scopeSeparator' => ' ',
            ]);

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
            'scope' => $this->getScopes(),
        ]);
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
            'code' => $code,
        ]);

        // user
        $graph = (new Graph())
            ->setAccessToken($token->getToken());

        /** @var \Microsoft\Graph\Model\User $me */
        $me = $graph
            ->createRequest("get", "/me")
            ->setReturnType(\Microsoft\Graph\Model\User::class)
            ->execute();
        $result = $me->getProperties();

        // The id token is a JWT token that contains information about the user
        // It's a base64 coded string that has a header, payload and signature
        // $idToken = $token->getValues()['id_token'];
        // $decodedAccessTokenPayload = base64_decode(
        //     explode('.', $idToken)[1]
        // );
        // $result = json_decode($decodedAccessTokenPayload, true);

        // $name = $this->pop($result, 'name');
        // $firstname = $lastname = null;
        // if ($name) {
        //    $explode = explode(' ', $name);
        //    if (isset($explode[0])) {
        //       $firstname = array_shift($explode);
        //       $lastname = implode(' ', $explode);
        //   }
        // }

        $email = $this->pop($result, 'mail');
        if (!$email) {
            $email = $this->pop($result, 'userPrincipalName');
        }

        $user = (new User($this->getType(), $token))
            ->setUid($this->pop($result, 'id'))
            ->setEmail($email)
            ->setVerified(true)
            ->setFirstName($this->pop($result, 'givenName'))
            ->setLastName($this->pop($result, 'surname'))
            ->setLocale($this->pop($result, 'preferredLanguage'))
            ->setExtraData($result)
        ;

        return $user;
    }
}
