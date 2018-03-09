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

use Cawa\App\AbstractApp;
use Cawa\App\HttpFactory;
use Cawa\Controller\AbstractController;
use Cawa\Oauth\Exceptions\InvalidState;
use Cawa\Oauth\Providers\Facebook;
use Cawa\Renderer\Element;
use Cawa\Router\RouterFactory;
use Cawa\Session\SessionFactory;

class Controller extends AbstractController
{
    use HttpFactory;
    use RouterFactory;
    use SessionFactory;

    /**
     * @param string $service
     * @param string $from
     * @param string $failed
     */
    public function start(string $service, string $from = null, string $failed = null)
    {
        if ($from) {
            self::session()->set(Module::SESSION_REDIRECT_URL, $from);
        }

        if ($failed) {
            self::session()->set(Module::SESSION_FAILURE_URL, $failed);
        }

        $provider = AbstractProvider::factory($service);
        self::response()->redirect((string) $provider->getAuthorizationUri());
    }

    /**
     * @param string $service
     *
     * @throws InvalidState
     */
    public function end(string $service)
    {
        $user = null;

        $provider = AbstractProvider::factory($service);
        if (!$provider->controlState(self::request()->getQuery('state'))) {
            $exception = new InvalidState($service);
        } else {
            $exception = $provider->controlError(self::request()->getQuery('error'));

            if (!$exception) {
                $user = $provider->getUser();
            }
        }

        self::session()->set(Module::SESSION_NAME, $exception ?: $user);

        /* @var \Cawa\Oauth\Module $module */
        $module = AbstractApp::instance()->getModule('Cawa\\Oauth\\Module');

        $url = self::session()->getFlush(Module::SESSION_REDIRECT_URL);
        $failed = self::session()->getFlush(Module::SESSION_FAILURE_URL);

        if (!$url) {
            $url = self::uri($module->getRedirectRoute());
        }

        if ($exception && $failed) {
            $url = $failed;
        }

        self::response()->redirect($url);
    }

    /**
     * @param string $service
     * @param string|null $from
     * @param string|null $failed
     *
     * @return string
     */
    public function client(string $service, string $from = null, string $failed = null)
    {
        if ($from) {
            self::session()->set(Module::SESSION_REDIRECT_URL, $from);
        }

        if ($failed) {
            self::session()->set(Module::SESSION_FAILURE_URL, $failed);
        }

        /* @var \Cawa\Oauth\Module $module */
        $module = AbstractApp::instance()->getModule('Cawa\\Oauth\\Module');

        /** @var Facebook $provider */
        $provider = AbstractProvider::factory($service);
        $masterpage = $provider->getClientMasterPage($from ?: self::uri($module->getRedirectRoute())->get(false), $failed);

        $masterpage->addCss('
            .spinner {
                width: 70px;
                text-align: center;
                top: 50%;
                margin-top: -11px;
                position: absolute;
                margin-left: -35px;
                left: 50%;
            }
            
            .spinner > div {
                width: 18px;
                height: 18px;
                background-color: #333;
            
                border-radius: 100%;
                display: inline-block;
                -webkit-animation: sk-bouncedelay 1.4s infinite ease-in-out both;
                animation: sk-bouncedelay 1.4s infinite ease-in-out both;
            }
            
            .spinner .bounce1 {
                -webkit-animation-delay: -0.32s;
                animation-delay: -0.32s;
            }
            
            .spinner .bounce2 {
                -webkit-animation-delay: -0.16s;
                animation-delay: -0.16s;
            }
            
            @-webkit-keyframes sk-bouncedelay {
                0%, 80%, 100% {
                    -webkit-transform: scale(0)
                }
                40% {
                    -webkit-transform: scale(1.0)
                }
            }
            
            @keyframes sk-bouncedelay {
                0%, 80%, 100% {
                    -webkit-transform: scale(0);
                    transform: scale(0);
                }
                40% {
                    -webkit-transform: scale(1.0);
                    transform: scale(1.0);
                }
            }
        ');

        $masterpage->getBody()->add(new Element('<div class="spinner">
          <div class="bounce1"></div>
          <div class="bounce2"></div>
          <div class="bounce3"></div>
        </div>'));

        return $masterpage->render();
    }
}
