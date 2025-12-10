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

use stdClass;
use JsonException;
use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\JsonSerializeException;

class Response
{
    /**
     * The API response body.
     *
     * @var string
     */
    protected string $body;

    /**
     * The API response data structure.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * The API errors instance.
     *
     * @var stdClass
     */
    protected stdClass $errors;

    /**
     * The list of functions included in the API request.
     *
     * @var array
     */
    protected array $functions;

    /**
     * Flag for determining if Api request was successful.
     *
     * @var bool|null
     */
    protected ?bool $success = null;

    /**
     * Create a new API response instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(array $requestFunctionsList, string $responseBody)
    {
        if (empty($requestFunctionsList)) {
            throw new InvalidValueException('Empty request function list provided.');
        }

        $this->body = $responseBody;
        $this->errors = new stdClass();
        $this->functions = $requestFunctionsList;

        $this->parseResponseBody($responseBody);
    }

    /**
     * Get the API response body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get the API response data property for specific function.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function getData(string $functionName, int $index = 0): stdClass
    {
        if (! $this->isValidFunction($functionName)) {
            $this->throwInvalidFunctionError($functionName);
        }

        if (! isset($this->data[$functionName][$index])) {
            throw new InvalidValueException(
                'Index "' . $index . '" does not exist for function "' . $functionName . '".'
            );
        }

        $functionData = $this->data[$functionName][$index];

        return $functionData->data ?? $functionData;
    }

    /**
     * Get the API response errors.
     */
    public function getErrors(): stdClass
    {
        return $this->errors;
    }

    /**
     * Get the full API response for specific function.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function getFunction(string $functionName): array
    {
        if (! $this->isValidFunction($functionName)) {
            $this->throwInvalidFunctionError($functionName);
        }

        return $this->data[$functionName];
    }

    /**
     * Get the list of functions included in the API response.
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Get the API response data.
     */
    public function getResponse(?string $functionName = null): array
    {
        if (! is_null($functionName)) {
            return $this->getFunction($functionName);
        }

        return $this->data;
    }

    /**
     * Flag for determining if the API response contains errors.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if a function name exists in the functions list.
     */
    protected function isValidFunction(string $functionName): bool
    {
        return isset($this->data[$functionName]);
    }

    /**
     * Parse the raw API response and set the response data.
     *
     * @throws \pdeans\Miva\Api\Exceptions\JsonSerializeException
     */
    protected function parseResponseBody(string $responseBody): void
    {
        try {
            $response = json_decode(json: $responseBody, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonSerializeException($exception->getMessage());
        }

        $functions = [];

        if (is_object($response)) {
            $this->success = (bool) $response->success;

            if ($this->success) {
                $functions = [$this->functions[0] => [$response]];
            } else {
                $this->errors->success = $this->success;
                $this->errors->code = (string) $response->error_code;
                $this->errors->message = (string) $response->error_message;
            }
        } elseif (is_array($response)) {
            $functionsCount = count($this->functions);

            foreach ($response as $index => $results) {
                $functionName = $this->functions[($functionsCount === 1 ? 0 : $index)];

                if (is_array($results)) {
                    foreach ($results as $result) {
                        $functions[$functionName][] = $result;
                    }
                } elseif (is_object($results)) {
                    $functions[$functionName][] = $results;
                }
            }
        }

        if (is_null($this->success) && empty(get_object_vars($this->errors))) {
            $this->success = true;
        }

        $this->data = $functions;
    }

    /**
     * Throw an invalid function name error.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    protected function throwInvalidFunctionError(string $functionName): void
    {
        throw new InvalidValueException('Function name "' . $functionName . '" invalid or missing from results list.');
    }
}
