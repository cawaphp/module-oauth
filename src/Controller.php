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

use Cawa\App\AbstractApp;
use Cawa\App\HttpFactory;
use Cawa\Controller\AbstractController;
use Cawa\Router\RouterFactory;
use Cawa\Session\SessionFactory;

class Controller extends AbstractController
{
    use HttpFactory;
    use RouterFactory;
    use SessionFactory;

    /**
     * @param string $service
     *
     * @return string
     */
    public function start(string $service)
    {
        $provider = AbstractProvider::create($service);
        $this->response()->redirect($provider->getAuthorizationUri());
    }

    /**
     * @param string $service
     *
     * @return string
     */
    public function end(string $service)
    {
        $provider = AbstractProvider::create($service);
        $user = $provider->getUser();

        self::session()->set(Module::SESSION_NAME, $user);
        self::session()->remove(SessionStorage::SESSION_VAR_STATE);
        self::session()->remove(SessionStorage::SESSION_VAR_TOKEN);

        /* @var \Cawa\Oauth\Module $module */
        $module = AbstractApp::instance()->getModule('Cawa\\Oauth\\Module');

        $url = $this->router()->getUri($module->getRedirectRoute());
        $this->response()->redirect($url);
    }
}
