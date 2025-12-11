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

declare(strict_types=1);

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Builders\FunctionBuilder;
use pdeans\Miva\Api\Builders\RequestBuilder;
use pdeans\Miva\Api\Exceptions\InvalidMethodCallException;
use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @method $this count(int $count)
 * @method $this filter(string $filterName, mixed $filterValue)
 * @method $this filters(array<string, mixed> $filters)
 * @method $this odc(array<int, string> $columns)
 * @method $this offset(int $offset)
 * @method $this ondemandcolumns(array<int, string> $columns)
 * @method $this params(array<string, mixed> $parameters)
 * @method $this passphrase(string $passphrase)
 * @method $this sort(string $sort)
 *
 * @mixin \pdeans\Miva\Api\Builders\FunctionBuilder
 */
class Client
{
    /**
     * Supported binary encoding values.
     *
     * @var string[]
     */
    private const BINARY_ENCODINGS = ['json', 'base64'];

    /**
     * Api Auth instance.
     *
     * @var \pdeans\Miva\Api\Auth|\pdeans\Miva\Api\SshAuth|null
     */
    protected Auth|SshAuth|null $auth = null;

    /**
     * Preferred payload encoding for the request body.
     *
     * Supports 'json' or 'base64', corresponding to the
     * X-Miva-API-Binary-Encoding header.
     *
     * @var string|null
     */
    protected ?string $binaryEncoding = null;

    /**
     * List of API HTTP request headers.
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Api configuration options.
     *
     * @var array<string, mixed>
     */
    protected array $options;

    /**
     * Defines the operations range for multi-call requests.
     *
     * Used with the Range header to resume multi-call batches
     * when a previous request returned a partial (206) response.
     *
     * @var string|null
     */
    protected ?string $rangeHeader = null;

    /**
     * Api Request instance.
     *
     * @var \pdeans\Miva\Api\Request|null
     */
    protected ?Request $request = null;

    /**
     * Api RequestBuilder instance.
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected RequestBuilder $requestBuilder;

    /**
     * Request timeout override in seconds.
     *
     * When set, this value is sent using the X-Miva-API-Timeout header
     * to control the maximum execution time for a single API request.
     *
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * Miva JSON API endpoint value.
     *
     * @var string
     */
    protected string $url;

    /**
     * Create a new client instance.
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);

        $this->createRequestBuilder();
        $this->setUrl($this->options['url']);

        if (! empty($this->options['http_headers'])) {
            $this->addHeaders($this->options['http_headers']);
        }
    }

    /**
     * Add API function to request function list.
     */
    public function add(?FunctionBuilder $function = null): static
    {
        if ($function !== null) {
            $this->requestBuilder->function = $function;
        }

        $this->requestBuilder->addFunction();

        return $this;
    }

    /**
     * Add HTTP request header.
     */
    public function addHeader(string $headerName, string $headerValue): static
    {
        $this->headers[$headerName] = $headerValue;

        return $this;
    }

    /**
     * Add list of HTTP request headers.
     *
     * @param array<string, string> $headers
     */
    public function addHeaders(array $headers): static
    {
        foreach ($headers as $headerName => $headerValue) {
            $this->addHeader($headerName, $headerValue);
        }

        return $this;
    }

    /**
     * Clear the current request builder instance.
     */
    protected function clearRequestBuilder(): static
    {
        $this->createRequestBuilder();

        return $this;
    }

    /**
     * Create a new request builder instance.
     */
    protected function createRequestBuilder(): static
    {
        $this->requestBuilder = new RequestBuilder(
            (string) $this->options['store_code'],
            isset($this->options['timestamp']) ? (bool) $this->options['timestamp'] : true
        );

        return $this;
    }

    /**
     * Create a new API function.
     */
    public function func(string $name): static
    {
        $this->requestBuilder->newFunction($name);

        return $this;
    }

    /**
     * Get the API request function list.
     *
     * @return array<string, array<FunctionBuilder>>
     */
    public function getFunctionList(): array
    {
        return $this->requestBuilder->getFunctionList();
    }

    /**
     * Get the list of API request headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the previous request instance.
     */
    public function getPreviousRequest(): ?RequestInterface
    {
        return $this->request?->request();
    }

    /**
     * Get the previous response instance.
     */
    public function getPreviousResponse(): ?ResponseInterface
    {
        return $this->request?->response();
    }

    /**
     * Get the API client options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the API Request instance.
     */
    public function getRequest(): Request
    {
        if (! $this->request instanceof Request) {
            $this->request = new Request(
                $this->requestBuilder,
                $this->options['http_client'] ?? null
            );
        }

        return $this->request;
    }

    /**
     * Get API request body.
     *
     * Available options for the $encodeOpts parameter:
     *   - @link https://php.net/manual/en/json.constants.php
     */
    public function getRequestBody(int $encodeOpts = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, int $depth = 512): string
    {
        return $this->getRequest()
            ->getBody($encodeOpts, $depth);
    }

    /**
     * Get the API endpoint url.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Refresh the request builder instance.
     */
    protected function refreshRequestBuilder(): static
    {
        $this->clearRequestBuilder();
        $this->createRequestBuilder();

        if ($this->request instanceof Request) {
            $this->request->setRequestBuilder($this->requestBuilder);
        }

        return $this;
    }

    /**
     * Send the API request.
     */
    public function send(bool $rawResponse = false): string|Response
    {
        $request = $this->getRequest();

        $request->setTimeoutHeader($this->timeout);
        $request->setBinaryEncoding($this->binaryEncoding);
        $request->setRangeHeader($this->rangeHeader);

        $response = $request->sendRequest($this->getUrl(), $this->auth, $this->getHeaders());

        // Save the function list names before clearing the request builder
        $functionList = $this->getFunctionList();

        // Refresh request builder
        $this->refreshRequestBuilder();

        $responseBody = (string) $response->getBody();

        return $rawResponse
            ? $responseBody
            : new Response($functionList, $responseBody, $response->getStatusCode(), $response->getHeaders());
    }

    /**
     * Set the API client options.
     *
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): static
    {
        $capabilities = $this->authCapabilities($options);
        $this->validateOptions($options, $capabilities);
        $this->options = $options;

        $this->configureOptions($options, $capabilities);

        return $this;
    }

    /**
     * Set the API endpoint url.
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Validate the client configuration options.
     *
     * @param array<string, mixed> $options
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    protected function validateOptions(array $options, ?array $capabilities = null): void
    {
        $capabilities ??= $this->authCapabilities($options);

        [$hasTokenAuth, $hasSshAuth] = $capabilities;

        if (! $hasTokenAuth && ! $hasSshAuth) {
            throw new MissingRequiredValueException(
                'Missing required authentication options. ' .
                'Provide access_token/private_key or ssh_auth username/private_key.'
            );
        }

        foreach (['store_code', 'url'] as $option) {
            if (empty($options[$option])) {
                throw new MissingRequiredValueException(
                    sprintf('Missing required option "%s".', $option)
                );
            }
        }
    }

    /**
     * Set a per-request timeout value (seconds).
     */
    public function setTimeout(int $seconds): static
    {
        if ($seconds <= 0) {
            throw new InvalidValueException('Timeout value must be greater than zero.');
        }

        $this->timeout = $seconds;

        $this->options['timeout'] = $seconds;

        return $this;
    }

    /**
     * Set the binary encoding mode.
     */
    public function setBinaryEncoding(string $encoding): static
    {
        $encoding = strtolower(trim($encoding));

        if (! in_array($encoding, self::BINARY_ENCODINGS, true)) {
            throw new InvalidValueException(
                'Binary encoding must be one of: "' . implode('", "', self::BINARY_ENCODINGS) . '".'
            );
        }

        $this->binaryEncoding = $encoding === 'json' ? null : $encoding;

        if ($encoding !== 'json') {
            $this->options['binary_encoding'] = $encoding;
        }

        return $this;
    }

    /**
     * Set the operations range header for multi-call retries.
     */
    public function setOperationsRange(int $start, ?int $end = null): static
    {
        if ($start < 1) {
            throw new InvalidValueException('Range start must be at least 1.');
        }

        if ($end !== null && $end < $start) {
            throw new InvalidValueException('Range end must be greater than or equal to the start value.');
        }

        $this->rangeHeader = $end === null
            ? 'Operations=' . $start . '-'
            : 'Operations=' . $start . '-' . $end;

        $this->options['range'] = $this->rangeHeader;

        return $this;
    }

    /**
     * Clear any previously set operations range header.
     */
    public function clearOperationsRange(): static
    {
        $this->rangeHeader = null;

        unset($this->options['range']);

        return $this;
    }

    /**
     * Configure SSH authentication for the request.
     */
    public function setSshAuth(string $username, string $privateKey, string $algorithm = 'sha256'): static
    {
        $this->auth = new SshAuth($username, $privateKey, $algorithm);

        $this->options['ssh_auth'] = [
            'username' => $username,
            'private_key' => $privateKey,
            'algorithm' => $algorithm,
        ];

        return $this;
    }

    /**
     * Configure options from provided configuration.
     *
     * @param array<string, mixed> $options
     */
    protected function configureOptions(array $options, ?array $capabilities = null): void
    {
        $capabilities ??= $this->authCapabilities($options);

        [$hasTokenAuth, $hasSshAuth] = $capabilities;

        if ($hasSshAuth) {
            $ssh = $options['ssh_auth'];

            $this->setSshAuth(
                (string) $ssh['username'],
                (string) $ssh['private_key'],
                isset($ssh['algorithm']) ? (string) $ssh['algorithm'] : 'sha256'
            );
        } elseif ($hasTokenAuth) {
            $this->auth = new Auth(
                (string) $options['access_token'],
                (string) $options['private_key'],
                isset($options['hmac']) ? (string) $options['hmac'] : 'sha256'
            );
        }

        if (isset($options['timeout'])) {
            $this->setTimeout((int) $options['timeout']);
        }

        if (isset($options['binary_encoding'])) {
            $this->setBinaryEncoding((string) $options['binary_encoding']);
        }

        if (isset($options['range']) && is_string($options['range']) && $options['range'] !== '') {
            $this->rangeHeader = $options['range'];
        }
    }

    /**
     * Determine available authentication options.
     *
     * @param array<string, mixed> $options
     * @return array{bool, bool}
     */
    protected function authCapabilities(array $options): array
    {
        $hasAccessToken = isset($options['access_token']) && (string) $options['access_token'] !== '';
        $hasPrivateKey = array_key_exists('private_key', $options);
        $hasTokenAuth = $hasAccessToken && $hasPrivateKey;

        $hasSshAuth = isset($options['ssh_auth']['username'], $options['ssh_auth']['private_key']);

        return [$hasTokenAuth, $hasSshAuth];
    }

    /**
     * Invoke \pdeans\Miva\Api\Builders\FunctionBuilder helper methods.
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): static
    {
        if (class_exists(FunctionBuilder::class) && in_array($method, get_class_methods(FunctionBuilder::class))) {
            $this->requestBuilder->function->{$method}(...$arguments);

            return $this;
        }

        throw new InvalidMethodCallException('Bad method call "' . $method . '".');
    }
}
