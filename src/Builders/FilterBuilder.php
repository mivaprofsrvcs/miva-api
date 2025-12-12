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

use Countable;
use pdeans\Miva\Api\Contracts\BuilderInterface;
use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

class FilterBuilder implements BuilderInterface
{
    /**
     * API function name - for use with 'show' filters.
     *
     * @var string|null
     */
    protected ?string $functionName;

    /**
     * Filter name.
     *
     * @var string
     */
    public string $name;

    /**
     * Filter value.
     *
     * @var mixed
     */
    public mixed $value;

    /**
     * Filter value list.
     *
     * @var array<int, SearchFilterBuilder>|GenericFilterBuilder|OnDemandColumnsFilterBuilder|ShowFilterBuilder
     */
    protected array|GenericFilterBuilder|OnDemandColumnsFilterBuilder|ShowFilterBuilder $valueList = [];

    /**
     * Create a new filter builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(string $name, mixed $value, ?string $functionName = null)
    {
        $this->name = trim($name);

        if ($this->name === '') {
            throw new InvalidValueException('Invalid value provided for "name".');
        }

        $this->value = $value;

        if ($this->isBlankValue($this->value)) {
            throw new InvalidValueException('Invalid value provided for "value".');
        }

        $this->functionName = $functionName;
    }

    /**
     * Add a filter to the filter value list.
     */
    public function addFilter(): static
    {
        $name = strtolower($this->name);

        if ($name === 'search') {
            $filters = [];

            if (! is_array($this->value)) {
                throw new MissingRequiredValueException('Search filter value must be an array.');
            }

            if (isset($this->value[0])) {
                foreach ($this->value as $searchFilter) {
                    $this->validateSearchFilter($searchFilter);

                    $filters[] = new SearchFilterBuilder(
                        $searchFilter['field'],
                        $searchFilter['operator'],
                        $searchFilter['value'] ?? null
                    );
                }
            } else {
                $this->validateSearchFilter($this->value);

                $filters[] = new SearchFilterBuilder(
                    $this->value['field'],
                    $this->value['operator'],
                    $this->value['value'] ?? null
                );
            }

            $this->valueList = $filters;
        } elseif ($name === 'ondemandcolumns') {
            $this->valueList = new OnDemandColumnsFilterBuilder($this->value);
        } elseif ($name === 'show') {
            if ($this->functionName === null) {
                throw new MissingRequiredValueException('Function name is required for show filters.');
            }

            $showFilter = new ShowFilterBuilder($this->functionName, $this->value);

            $this->name = $showFilter->getFilterName();
            $this->valueList = $showFilter;
        } else {
            $this->valueList = new GenericFilterBuilder($this->value);
        }

        return $this;
    }

    /**
     * Determine if a filter value is blank.
     */
    protected function isBlankValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_bool($value) || is_numeric($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'value' => $this->valueList,
        ];
    }

    /**
     * Validate a search filter.
     *
     * @param array<string, mixed> $filter
     *
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    protected function validateSearchFilter(array $filter): void
    {
        if (! isset($filter['field'])) {
            throw new MissingRequiredValueException('Missing required filter property "field".');
        }

        if (! isset($filter['operator'])) {
            throw new MissingRequiredValueException('Missing required filter property "operator".');
        }

        if (
            ! isset($filter['value'])
            && ! in_array(strtoupper($filter['operator']), SearchFilterBuilder::getNullOperators())
        ) {
            throw new MissingRequiredValueException('Missing required filter property "value".');
        }
    }
}
