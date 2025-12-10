<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace pdeans\Miva\Api;

use JsonException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request as PsrRequest;
use pdeans\Miva\Api\Builders\RequestBuilder;
use pdeans\Miva\Api\Exceptions\JsonSerializeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * API Request class
 */
class Request
{
    /**
     * API request body.
     *
     * @var string
     */
    protected string $body = '';

    /**
     * HTTP client (cURL) instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $client;

    /**
     * API request headers.
     *
     * @var array
     */
    protected array $headers;

    /**
     * The HTTP request instance.
     *
     * @var \Psr\Http\Message\RequestInterface|null
     */
    protected RequestInterface|null $request = null;

    /**
     * The HTTP response instance.
     *
     * @var \Psr\Http\Message\ResponseInterface|null
     */
    protected ResponseInterface|null $response = null;

    /**
     * The API request builder instance.
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected RequestBuilder $requestBuilder;

    /**
     * Create a new API request instance.
     */
    public function __construct(RequestBuilder $requestBuilder, ClientInterface|array|null $client = null)
    {
        $this->headers = ['Content-Type' => 'application/json'];
        $this->client = $this->resolveClient($client);

        $this->setRequestBuilder($requestBuilder);
    }

    /**
     * Get the API request body.
     *
     * @link https://php.net/manual/en/json.constants.php
     *
     * @throws \pdeans\Miva\Api\Exceptions\JsonSerializeException
     */
    public function getBody(int $encodeOpts = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, int $depth = 512): string
    {
        try {
            $this->body = json_encode($this->requestBuilder, $encodeOpts, $depth);
        } catch (JsonException $exception) {
            throw new JsonSerializeException($exception->getMessage());
        }

        return $this->body;
    }

    /**
     * Get the request builder instance.
     */
    public function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }

    /**
     * Release the client request handler.
     */
    public function releaseClient(): void
    {
        $this->request = null;
        $this->response = null;
    }

    /**
     * Get the API request.
     */
    public function request(): RequestInterface|null
    {
        return $this->request;
    }

    /**
     * Get the previous API response.
     */
    public function response(): ResponseInterface|null
    {
        return $this->response;
    }

    /**
     * Send an API request.
     */
    public function sendRequest(string $url, Auth $auth, array $httpHeaders = []): ResponseInterface
    {
        $this->response = null;

        $body = $this->getBody();

        $headers = array_merge(
            $this->headers,
            $httpHeaders,
            $auth->getAuthHeader($body)
        );

        $this->request = new PsrRequest('POST', $url, $headers, $body);

        $this->response = $this->client->send($this->request, [
            'http_errors' => false,
        ]);

        return $this->response;
    }

    /**
     * Set the request builder instance.
     */
    public function setRequestBuilder(RequestBuilder $requestBuilder): static
    {
        $this->requestBuilder = $requestBuilder;

        return $this;
    }

    /**
     * Resolve HTTP client instance from provided configuration.
     */
    protected function resolveClient(ClientInterface|array|null $client): ClientInterface
    {
        if ($client instanceof ClientInterface) {
            return $client;
        }

        if (is_array($client)) {
            if (isset($client['client']) && $client['client'] instanceof ClientInterface) {
                return $client['client'];
            }

            $clientOptions = $client;

            unset($clientOptions['client']);

            return new Client($clientOptions);
        }

        return new Client([]);
    }
}
