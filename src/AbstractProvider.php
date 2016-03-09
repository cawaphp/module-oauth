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

namespace Cawa\Oauth;

use Cawa\App\App;
use Cawa\Core\DI;
use Cawa\Oauth;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Service\ServiceInterface;
use OAuth\ServiceFactory;

abstract class AbstractProvider
{
    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    public static function create(string $service) : AbstractProvider
    {
        // persistent storage to save the token
        $storage = new SessionStorage();

        // Setup the credentials for the requests
        $credentials = new Credentials(
            DI::config()->get('socials/' . $service . '/key'),
            DI::config()->get('socials/' . $service . '/secret'),
            App::router()->getUri('oauth.end', ['service' => $service], true)
        );

        // Oauth Service
        $serviceFactory = new ServiceFactory();
        $serviceFactory->setHttpClient(new HttpClient());

        $class = 'Cawa\\Oauth\\Providers\\' . ucfirst($service);

        /** @var AbstractProvider $provider */
        $provider = new $class();
        $provider->type = $service;

        // Instantiate the service using the credentials, http client and storage mechanism for the token
        $scopes = DI::config()->getIfExists('socials/' . $service . '/scopes');

        if (!$scopes && defined($class . '::DEFAULT_SCOPES')) {
            $scopes = constant($class . '::DEFAULT_SCOPES');
        }

        if ($scopes) {
            $service = $serviceFactory->createService($service, $credentials, $storage, $scopes);
        } else {
            $service = $serviceFactory->createService($service, $credentials, $storage);
        }

        $provider->service = $service;

        return $provider;
    }

    /**
     * @var ServiceInterface
     */
    protected $service;

    /**
     * @param array $array
     * @param string|array $key
     *
     * @return null|mixed
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
     * @return User|Oauth\Exceptions\Denied
     */
    abstract public function getUser();

    /**
     * @return string
     */
    public function getAuthorizationUri() : string
    {
        $url = $this->service->getAuthorizationUri();

        return $url->__toString();
    }
}
