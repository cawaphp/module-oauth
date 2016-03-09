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
use Cawa\App\Controller\AbstractController;
use Cawa\Session\SessionFactory;

class Controller extends AbstractController
{
    use SessionFactory;

    /**
     * @param string $service
     *
     * @return string
     */
    public function start(string $service)
    {
        $provider = AbstractProvider::create($service);
        App::response()->redirect($provider->getAuthorizationUri());
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
        $module = App::instance()->getModule('Cawa\\Oauth\\Module');

        $url = App::router()->getUri($module->getRedirectRoute());
        App::response()->redirect($url);
    }
}
