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
use Cawa\Router\Route;

class Module extends \Cawa\App\Module
{
    /**
     * the session variable with User Object or Error Object
     */
    const SESSION_NAME = 'OAUTH';

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
     * @param string $redirectRoute
     */
    public function __construct(string $redirectRoute)
    {
        $this->redirectRoute = $redirectRoute;
    }

    /**
     * @return bool
     */
    public function init() : bool
    {
        $providers = '{{C:<service>(twitter|facebook|microsoft|google)}}';

        App::router()->addRoutes([
            Route::create()->setName('oauth.start')
                ->setMatch("/oauth/$providers/start")
                ->setController('Cawa\\Oauth\\Controller::start')
        ]);

        App::router()->addRoutes([
            Route::create()->setName('oauth.end')
                ->setMatch("/oauth/$providers/end")
                ->setController('Cawa\\Oauth\\Controller::end')
        ]);

        return true;
    }
}
