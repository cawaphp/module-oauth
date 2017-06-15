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

use Cawa\Http\Request;
use Cawa\HttpClient\HttpClient as BaseHttpClient;
use Cawa\HttpClient\HttpClientFactory;
use Cawa\Net\Uri;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\UriInterface;

class HttpClient implements ClientInterface
{
    use HttpClientFactory;

    /**
     * @var BaseHttpClient
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    public function retrieveResponse(
        UriInterface $endpoint,
        $requestBody,
        array $extraHeaders = [],
        $method = 'POST'
    ) {
        if (!$this->client) {
            $this->client = self::httpClient(self::class, false);
        }

        $request = new Request(new Uri($endpoint->getAbsoluteUri()));
        $request->setMethod($method);

        if ($requestBody && is_array($requestBody)) {
            $request->setPosts($requestBody);
        }

        foreach ($extraHeaders as $name => $value) {
            $request->addHeader($name, $value);
        }

        $response = $this->client->send($request);

        return $response->getBody();
    }
}
