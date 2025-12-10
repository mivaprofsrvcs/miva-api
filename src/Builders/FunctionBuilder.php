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

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Contracts\BuilderInterface;
use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

class FunctionBuilder implements BuilderInterface
{
    /**
     * Number of records to return.
     *
     * @var int|null
     */
    public ?int $count = null;

    /**
     * List of filter builder objects.
     *
     * @var \pdeans\Miva\Api\Builders\FilterBuilder[]
     */
    public array $filterList = [];

    /**
     * Function name.
     *
     * @var string
     */
    public string $name;

    /**
     * Number of records to offset the return count.
     *
     * @var int|null
     */
    public ?int $offset = null;

    /**
     * Function parameter list.
     *
     * @var array
     */
    public array $parameterList = [];

    /**
     * Encryption passphrase.
     *
     * @var string|null
     */
    public ?string $passphrase = null;

    /**
     * Sort records modifier.
     *
     * @var string|null
     */
    public ?string $sort = null;

    /**
     * Create a new function builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    public function __construct(string $name)
    {
        if (trim($name) === '') {
            throw new MissingRequiredValueException('Invalid function name "' . $name . '" provided.');
        }

        $this->name = $name;
    }

    /**
     * Set the number of records to return.
     */
    public function count(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Create a filter and add it to the filter list.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function filter(string $filterName, mixed $filterValue): static
    {
        if (trim($filterName) === '') {
            throw new InvalidValueException('Invalid value "' . $filterName . '" provided for filter name.');
        }

        $this->filterList[] = (new FilterBuilder($filterName, $filterValue))->addFilter();

        return $this;
    }

    /**
     * Add a list of filters to the filter list.
     */
    public function filters(array $filters): static
    {
        foreach ($filters as $filterName => $filterValue) {
            $this->filter($filterName, $filterValue);
        }

        return $this;
    }

    /**
     * Format a function parameter name.
     */
    public function formatParameterName(string $paramName): string
    {
        return mb_convert_case($paramName, MB_CASE_TITLE);
    }

    /**
     * Get the list of common parameters.
     *
     * This list maps corresponding helper methods within the class, with the
     * method name matching the parameter list value.
     */
    public function getCommonParameterList(): array
    {
        return [
            'count',
            'offset',
            'passphrase',
            'sort',
        ];
    }

    /**
     * Get the full parameter list.
     */
    public function getParameterList(): array
    {
        return array_merge($this->getCommonParameterList(), $this->parameterList);
    }

    /**
     * Get the request parameters.
     */
    public function getRequestParameters(): array
    {
        $function = [];

        foreach ($this->getCommonParameterList() as $parameter) {
            if (isset($this->{$parameter})) {
                $function[$this->formatParameterName($parameter)] = $this->{$parameter};
            }
        }

        if (! empty($this->parameterList)) {
            foreach ($this->parameterList as $name => $value) {
                $function[$this->formatParameterName($name)] = $value;
            }
        }

        if (! empty($this->filterList)) {
            $function['Filter'] = $this->filterList;
        }

        return $function;
    }

    /**
     * Define the JSON serialization format.
     */
    public function jsonSerialize(): array
    {
        return $this->getRequestParameters();
    }

    /**
     * Set the offset of the first record to return.
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Shorthand method to set the ondemandcolumns filter list.
     */
    public function odc(array $columns): static
    {
        $this->ondemandcolumns($columns);

        return $this;
    }

    /**
     * Set the ondemandcolumns filter list
     */
    public function ondemandcolumns(array $columns): static
    {
        $this->filter('ondemandcolumns', $columns);

        return $this;
    }

    /**
     * Set additional function input parameters.
     */
    public function params(array $parameters): static
    {
        $this->parameterList = $parameters;

        return $this;
    }

    /**
     * Set the passphrase parameter.
     */
    public function passphrase(string $passphrase): static
    {
        $this->passphrase = $passphrase;

        return $this;
    }

    /**
     * Add a search filter to the filter list.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function search(mixed ...$args): static
    {
        $argsCount = func_num_args();

        if ($argsCount < 1 || $argsCount > 3) {
            throw new InvalidValueException('Invalid arguments supplied for "' . __METHOD__ . '".');
        }

        if ($argsCount === 1) {
            if (! is_array($args[0]) || empty($args[0])) {
                throw new InvalidValueException('Invalid arguments supplied for "' . __METHOD__ . '".');
            }

            $this->filter('search', $args[0]);
        } else {
            $field = array_shift($args);

            [$operator, $value] = SearchFilterBuilder::getOperatorAndValue(...$args);

            $this->filter('search', [
                'field' => $field,
                'operator' => strtoupper($operator),
                'value' => $value,
            ]);
        }

        return $this;
    }

    /**
     * Add a show filter to the filter list.
     */
    public function show(string $showValue): static
    {
        $this->filterList[] = (new FilterBuilder('show', $showValue, $this->name))->addFilter();

        return $this;
    }

    /**
     * Set the column to sort results.
     */
    public function sort(string $sortColumn): static
    {
        $this->sort = strtolower($sortColumn);

        return $this;
    }

    /**
     * Set the column to sort results in descending order.
     */
    public function sortDesc(string $sortColumn): static
    {
        $this->sort = '-' . strtolower(ltrim($sortColumn, '-'));

        return $this;
    }
}
