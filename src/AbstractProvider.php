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

namespace Cawa\Oauth;

use Cawa\Core\DI;
use Cawa\Oauth;
use Cawa\Router\RouterFactory;
use Cawa\Session\SessionFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use League\OAuth1\Client\Server\Server;
use League\OAuth2\Client\Provider\AbstractProvider as AbstractProviderBase;

abstract class AbstractProvider
{
    use RouterFactory;
    use SessionFactory;

    const TYPE_FACEBOOK = 'Facebook';
    const TYPE_GOOGLE = 'Google';
    const TYPE_LIVE = 'Live';
    const TYPE_MICROSOFT = 'Microsoft';
    const TYPE_TWITTER = 'Twitter';
    const TYPE_YAHOO = 'Yahoo';

    /**
     * @var string
     */
    protected $type;

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @var string
     */
    private $key;

    /**
     * @return string
     */
    protected function getKey() : string
    {
        return $this->key;
    }

    /**
     * @var string
     */
    private $secret;

    /**
     * @return string
     */
    protected function getSecret() : string
    {
        return $this->secret;
    }

    /**
     * @var array
     */
    private $scopes = [];

    /**
     * @return array
     */
    protected function getScopes() : array
    {
        return $this->scopes;
    }

    /**
     * @param array $scopes
     *
     * @return self
     */
    public function setScopes(array $scopes) : self
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @var array
     */
    private $options = [];

    /**
     * @return array
     */
    protected function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function setOptions(array $options) : self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @return string
     */
    protected function getRedirectUrl() : string
    {
        return $this->redirectUrl;
    }

    /**
     * @return Client
     */
    public function getHttpClient() : Client
    {
        if (!class_exists('Cawa\Clockwork\DataSource\Guzzle')) {
            return null;
        }

        $stack = HandlerStack::create();
        $stack->setHandler(new CurlHandler());
        $stack->push(new \Cawa\Clockwork\DataSource\Guzzle());
        return new Client(['handler' => $stack]);
    }

    /**
     * @param string|null $state
     *
     * @throws Exceptions\NoStateFound
     * @throws Exceptions\InvalidState
     *
     * @return bool
     */
    public function controlState(string $state = null) : bool
    {
        if (!$state) {
            throw new Oauth\Exceptions\NoStateFound($this->type);
        }

        $current = self::session()->getFlush(Module::SESSION_STATE);

        if ($current !== $state) {
            throw new Oauth\Exceptions\InvalidState($this->type);
        }

        return true;
    }

    /**
     * @param string|null $error
     *
     * @return Exceptions\Denied
     * @throws \Exception
     */
    public function controlError(string $error = null) : ?Oauth\Exceptions\Denied
    {
        if ($error == 'access_denied') {
            return new Oauth\Exceptions\Denied($this->getType(), sprintf("Error Code '%s'", $error));
        } elseif ($error) {
            throw new \Exception(sprintf("Failed with error '%s'", $error));
        }

        return null;
    }

    /**
     * @param string $serviceName
     *
     * @return AbstractProvider
     */
    public static function factory(string $serviceName) : AbstractProvider
    {
        $class = 'Cawa\\Oauth\\Providers\\' . constant(self::class . '::TYPE_' . strtoupper($serviceName));

        /** @var AbstractProvider $provider */
        $provider = new $class();
        $provider->key = DI::config()->get('socials/' . strtolower($serviceName) . '/key');
        $provider->secret = DI::config()->get('socials/' . strtolower($serviceName)  . '/secret');
        $provider->redirectUrl = self::uri('oauth/end', ['service' => strtolower($serviceName)])->get(false);

        if ($scopes = DI::config()->getIfExists('socials/' . strtolower($serviceName) . '/scopes')) {
            $provider->scopes = $scopes;
        } else if (defined($class . '::DEFAULT_SCOPES') && $scopes = constant($class . '::DEFAULT_SCOPES')) {
            $provider->scopes = $scopes;
        }

        return $provider;
    }

    /**
     * @return AbstractProviderBase|Server
     */
    abstract public function getService();

    /**
     * @return string
     */
    public function getAuthorizationUri() : string
    {
        $state = bin2hex(random_bytes(32 / 2));
        self::session()->set(Module::SESSION_STATE, $state);

        return $this->getService()->getAuthorizationUrl([
            'state' => $state,
            'scopes' => $this->scopes,
        ]);
    }

    /**
     * @param array $array
     * @param string|array $key
     *
     * @return null|string|bool
     */
    protected function pop(array &$array, $key)
    {
        if (is_string($key)) {
            if (isset($array[$key])) {
                $return = $array[$key];
                unset($array[$key]);

                return $return;
            }
        } else {
            if (isset($array[$key[0]][$key[1]])) {
                $return = $array[$key[0]][$key[1]];
                unset($array[$key[0]][$key[1]]);

                return $return;
            }
        }

        return null;
    }

    /**
     * @throws Oauth\Exceptions\NoStateFound
     * @throws Oauth\Exceptions\InvalidState
     *
     * @return User|Oauth\Exceptions\Denied
     */
    abstract public function getUser();
}
