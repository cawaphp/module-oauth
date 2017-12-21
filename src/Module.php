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

use Cawa\Router\Route;
use Cawa\Router\RouterFactory;
use Cawa\Router\UserInput;

class Module extends \Cawa\App\Module
{
    use RouterFactory;

    /**
     * the session variable with User Object or Error Object.
     */
    const SESSION_NAME = 'OAUTH';

    /**
     * the redirect url.
     */
    const SESSION_REDIRECT_URL = 'OAUTH-R';

    /**
     * the failure redirect url.
     */
    const SESSION_FAILURE_URL = 'OAUTH-F';

    /**
     * the state
     */
    const SESSION_STATE = 'STATE';

    /**
     * @var string
     */
    private $redirectRoute;

    /**
     * @return string
     */
    public function getRedirectRoute() : string
    {
        return $this->redirectRoute;
    }

    /**
     * @var array
     */
    private $enabledAuths = [];

    /**
     * @return array
     */
    public function getEnabledAuths() : array
    {
        if (sizeof($this->enabledAuths) > 0) {
            return $this->enabledAuths;
        }

        $return = [];
        $reflection = new \ReflectionClass(AbstractProvider::class);

        foreach ($reflection->getConstants() as $key => $constant) {
            if (stripos($key, 'TYPE_') !== false) {
                $return[] = $constant;
            }
        }

        return $return;

    }

    /**
     * @param string $redirectRoute
     * @param array $enabledAuths
     */
    public function __construct(string $redirectRoute, array $enabledAuths = [])
    {
        $this->redirectRoute = $redirectRoute;
        $this->enabledAuths = $enabledAuths;
    }

    /**
     * @return bool
     */
    public function init() : bool
    {
        $auths = array_map('strtolower', $this->getEnabledAuths());

        $providers = '{{C:<service>(' . implode('|', $auths) . ')}}';

        self::router()->addRoutes([
            (new Route())->setName('oauth/start')
                ->setMatch("/oauth/$providers/start")
                ->setController('Cawa\\Oauth\\Controller::start')
                ->setUserInputs([
                    new UserInput('from', 'string'),
                    new UserInput('failed', 'string'),
                ]),
        ]);

        if (in_array(AbstractProvider::TYPE_FACEBOOK, $this->getEnabledAuths())) {
            self::router()->addRoutes([
                (new Route())->setName('oauth/client')
                    ->setMatch('/oauth/{{C:<service>(facebook)}}/client')
                    ->setController('Cawa\\Oauth\\Controller::client')
                    ->setUserInputs([
                        new UserInput('from', 'string'),
                        new UserInput('failed', 'string'),
                    ]),
            ]);
        }

        self::router()->addRoutes([
            (new Route())->setName('oauth/end')
                ->setMatch("/oauth/$providers/end")
                ->setController('Cawa\\Oauth\\Controller::end'),
        ]);

        return true;
    }
}
