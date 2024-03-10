<?php

namespace App\Domain\Support\Apis;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

abstract class BaseApi
{
    protected ClientInterface $client;
    protected Configuration $config;

    public function __construct(?ClientInterface $client = null, ?Configuration $config = null)
    {
        $this->client = $client ?: new Client();
        $this->config = $config ?: new Configuration();
    }

    /**
     * @throws ApiException
     * @throws GuzzleException
     */
    protected function send(RequestBuilder $request, Closure $fn)
    {
        try {
            $response = $this->client->send($request->build($this->config, $this->client));
        } catch (RequestException $e) {
            throw new ApiException("[{$e->getCode()}] {$e->getMessage()}", $e->getCode(), $e->getResponse());
        } catch (\Exception $e) {
            dd($e);
        }

        return $fn($this->deserialize($response));
    }

    protected function sendAsync(RequestBuilder $request, Closure $fn): PromiseInterface
    {
        return $this->client
            ->sendAsync($request->build($this->config, $this->client))
            ->then(
                function ($response) use ($fn, $request) {
                    return $fn($this->deserialize($response));
                },
                function ($e) {
                    if (!($e instanceof RequestException)) {
                        throw $e;
                    }

                    throw new ApiException("[{$e->getCode()}] {$e->getMessage()}", $e->getCode(), $e->getResponse());
                }
            );
    }

    protected function deserialize(ResponseInterface $response): mixed
    {
        return json_decode((string)$response->getBody(), true);
    }
}
