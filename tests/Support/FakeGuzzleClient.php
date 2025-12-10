<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;

class FakeGuzzleClient implements ClientInterface
{
    /**
     * @var \Psr\Http\Message\RequestInterface|null
     */
    public ?RequestInterface $captured = null;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected ResponseInterface $response;

    public function __construct(?ResponseInterface $response = null)
    {
        $this->response = $response ?? new GuzzleResponse(200, [], '{"success":1,"data":{}}');
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $this->captured = $request;

        return $this->response;
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        $this->captured = $request;

        return Create::promiseFor($this->response);
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->response;
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return Create::promiseFor($this->response);
    }

    public function getConfig(?string $option = null)
    {
        return null;
    }
}
