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
use pdeans\Miva\Api\Response\ErrorBag;
use pdeans\Miva\Api\Response\Error;

class Response
{
    /**
     * The API response body.
     *
     * @var string
     */
    protected string $body;

    /**
     * Parsed response data grouped by function name
     * and iteration/operation index.
     *
     * @var array<string, array<int, mixed>>
     */
    protected array $data = [];

    /**
     * Error bag for the full response.
     */
    protected ErrorBag $errors;

    /**
     * Parsed result metadata grouped by function.
     *
     * @var array<int, array{name: string, count: int}>
     */
    protected array $functionMeta = [];

    /**
     * Unique function names included in the API request.
     *
     * @var array<int, string>
     */
    protected array $functions = [];

    /**
     * Track if any result reported failure.
     */
    protected bool $hasFailure = false;

    /**
     * Overall success flag.
     */
    protected bool $success = false;

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
        $this->functionMeta = $this->normalizeFunctionMeta($requestFunctionsList);
        $this->functions = array_values(
            array_unique(array_column($this->functionMeta, 'name'))
        );
        $this->errors = new ErrorBag();

        $this->parseResponseBody($responseBody);
        $this->success = ! $this->errors->has();
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
    public function getData(string $functionName, int $index = 0): array|object
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

        $payload = $functionData->data ?? $functionData;

        return is_array($payload) || is_object($payload)
            ? $payload
            : (object) $payload;
    }

    /**
     * Get the API response errors.
     */
    public function getErrors(): ErrorBag
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
        if ($functionName !== null) {
            return $this->getFunction($functionName);
        }

        return $this->data;
    }

    /**
     * Flag for determining if the API response contains errors.
     *
     * Alias of "successful" method.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Determine if the response is successful.
     */
    public function successful(): bool
    {
        return $this->success;
    }

    /**
     * Determine if the response failed.
     */
    public function failed(): bool
    {
        return ! $this->success;
    }

    /**
     * Determine if the response contains any errors.
     */
    public function hasErrors(): bool
    {
        return $this->errors->has();
    }

    /**
     * Get the response errors bag.
     */
    public function errors(): ErrorBag
    {
        return $this->errors;
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

        if (is_object($response)) {
            $meta = $this->functionMeta[0] ?? ['name' => $this->functions[0] ?? '', 'count' => 1];

            $this->addResult($meta['name'], 0, $response);
        } elseif (is_array($response)) {
            $metaCount = count($this->functionMeta);

            if ($metaCount === 1) {
                $functionName = $this->functionMeta[0]['name'];

                foreach ($response as $iterationIndex => $result) {
                    $this->addResult($functionName, $iterationIndex, $result);
                }
            } else {
                foreach ($response as $operationIndex => $result) {
                    if (! isset($this->functionMeta[$operationIndex])) {
                        continue;
                    }

                    $functionName = $this->functionMeta[$operationIndex]['name'];

                    if (is_array($result)) {
                        foreach ($result as $iterationIndex => $iterationResult) {
                            $this->addResult($functionName, $iterationIndex, $iterationResult);
                        }
                    } else {
                        $this->addResult($functionName, 0, $result);
                    }
                }
            }
        }

        $this->success = ! $this->hasFailure && ! $this->errors->has();
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

    /**
     * Add a parsed result to the response store and aggregate errors.
     */
    protected function addResult(string $functionName, int $index, mixed $result): void
    {
        if (! is_object($result)) {
            return;
        }

        $success = isset($result->success) ? (bool) $result->success : false;
        $errors = $success ? new ErrorBag() : $this->buildErrorBag($functionName, $index, $result);

        $this->data[$functionName][$index] = $result;
        $this->errors = $this->errors->merge($errors);

        if (! $success || $errors->has()) {
            $this->hasFailure = true;
        }
    }

    /**
     * Build an error bag from a response object.
     */
    protected function buildErrorBag(string $functionName, int $index, stdClass $result): ErrorBag
    {
        if (! isset($result->error_code) && ! isset($result->error_message)) {
            return new ErrorBag();
        }

        $errorFields = [];

        if (isset($result->error_fields) && is_array($result->error_fields)) {
            foreach ($result->error_fields as $field) {
                if (! is_object($field)) {
                    continue;
                }

                $errorFields[] = [
                    'error_field' => isset($field->error_field) ? (string) $field->error_field : null,
                    'error_message' => isset($field->error_message) ? (string) $field->error_message : null,
                ];
            }
        }

        $error = new Error(
            code: isset($result->error_code) ? (string) $result->error_code : '',
            message: isset($result->error_message) ? (string) $result->error_message : '',
            field: isset($result->error_field) ? (string) $result->error_field : null,
            fieldMessage: isset($result->error_field_message) ? (string) $result->error_field_message : null,
            validationError: (bool) ($result->validation_error ?? false),
            inputErrors: (bool) ($result->input_errors ?? false),
            errorFields: $errorFields,
            functionName: $functionName,
            index: $index
        );

        return new ErrorBag([$error]);
    }

    /**
     * Normalize function metadata from the request function list.
     */
    protected function normalizeFunctionMeta(array $functionList): array
    {
        $meta = [];
        $isAssoc = ! array_is_list($functionList);

        if ($isAssoc) {
            foreach ($functionList as $name => $functions) {
                $count = is_array($functions) ? max(1, count($functions)) : 1;

                $meta[] = [
                    'name' => (string) $name,
                    'count' => $count,
                ];
            }
        } else {
            foreach ($functionList as $name) {
                $meta[] = [
                    'name' => (string) $name,
                    'count' => 1,
                ];
            }
        }

        return $meta;
    }
}
