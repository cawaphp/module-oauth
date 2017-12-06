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
use Cawa\Oauth\Exceptions\NoStateFound;
use Cawa\Oauth\Module;
use Cawa\Oauth\User;
use Cawa\Session\SessionFactory;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\Twitter as BaseService;

class Twitter extends AbstractProvider
{
    use HttpFactory;
    use SessionFactory;

    protected $type = self::TYPE_TWITTER;

    /**
     * @var BaseService
     */
    private $service;

    /**
     * @return Server
     */
    public function getService() : Server
    {
        if (!$this->service) {
            $this->service = new BaseService(array_merge($this->getOptions(), [
                'identifier'     => $this->getKey(),
                'secret' => $this->getSecret(),
                'callback_uri'  => $this->getRedirectUrl(),
            ]));
        }

        return $this->service;
    }

    /**
     * @return string
     */
    public function getAuthorizationUri() : string
    {
        $temporaryCredentials = $this->getService()->getTemporaryCredentials();
        self::session()->set(
            Module::SESSION_STATE,
            json_encode([
                $temporaryCredentials->getIdentifier(),
                $temporaryCredentials->getSecret()
            ])
        );

        return $this->getService()->getAuthorizationUrl($temporaryCredentials);
    }

    /**
     * @var TemporaryCredentials
     */
    private $temporaryCredentials;

    /**
     * @param string|null $state
     *
     * @throws NoStateFound
     *
     * @return bool
     */
    public function controlState(string $state = null) : bool
    {
        $current = self::session()->getFlush(Module::SESSION_STATE);

        if (!$current) {
            throw new NoStateFound($this->getType());
        }

        $current = json_decode($current);

        $this->temporaryCredentials = new TemporaryCredentials();
        $this->temporaryCredentials->setIdentifier($current[0]);
        $this->temporaryCredentials->setSecret($current[1]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser() : User
    {
        $oauthToken = self::request()->getQuery('oauth_token');
        $oauthVerifier = self::request()->getQuery('oauth_verifier');

        if (!$oauthToken || !$oauthVerifier) {
            throw new \LogicException('No code found on oauth route end');
        }

        $tokenCredentials = $this->getService()->getTokenCredentials($this->temporaryCredentials, $oauthToken, $oauthVerifier);

        $twitterUser = $this->getService()->getUserDetails($tokenCredentials);
        $result = $this->transformUser($twitterUser);

        $name = $this->pop($result, 'name');
        $firstname = $lastname = null;
        if ($name) {
            $explode = explode(' ', $name);
            if (isset($explode[0])) {
                $firstname = array_shift($explode);
                $lastname = implode(' ', $explode);
            }
        }

        $email = $this->pop($result, 'email');

        $user = new User($this->getType(), $tokenCredentials);
        $user->setUid($this->pop($result, 'id_str'))
            ->setEmail($email)
            ->setUsername($this->pop($result, 'screen_name'))
            ->setVerified(!empty($email))
            ->setFirstName($firstname)
            ->setLastName($lastname)
            ->setLocale($this->pop($result, 'lang'))
            ->setExtraData($result)
        ;

        return $user;
    }

    /**
     * @param \League\OAuth1\Client\Server\User $twitterUser
     *
     * @return array
     */
    private function transformUser(\League\OAuth1\Client\Server\User $twitterUser) : array
    {
        $result = $twitterUser->extra;

        $result['id_str'] = $twitterUser->uid;
        $result['screen_name'] = $twitterUser->nickname;
        $result['name'] = $twitterUser->name;

        if ($twitterUser->email) {
            $result['email'] = $twitterUser->email;
        }

        if ($twitterUser->location) {
            $result['location'] = $twitterUser->location;
        }

        if ($twitterUser->description) {
            $result['description'] = $twitterUser->location;
        }

        if ($twitterUser->imageUrl) {
            $result['profile_image_url'] = $twitterUser->imageUrl;
        }

        foreach ($twitterUser->urls as $key => $url) {
            $result[$key] = $url;
        }

        return $result;
    }
}
