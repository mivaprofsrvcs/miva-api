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

use pdeans\Miva\Api\Exceptions\InvalidValueException;

class ShowFilterBuilder extends FilterBuilder
{
    /**
     * List of valid API function names.
     *
     * @var array<int, string>
     */
    protected const FUNCTION_NAMES = [
        'categorylist_load_query',
        'categoryproductlist_load_query',
        'productlist_load_query',
    ];

    /**
     * Show filter value.
     *
     * @var string
     */
    public string $showValue;

    /**
     * Create a new show filter builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(string $functionName, string $showValue)
    {
        $this->functionName = strtolower($functionName);

        if (! $this->isValidFunctionName($this->functionName)) {
            throw new InvalidValueException('Show filter is not supported for function "' . $functionName . '".');
        }

        $this->showValue = ucfirst($showValue);

        if (! $this->isValidShowValue($this->showValue)) {
            throw new InvalidValueException('Invalid value "' . $showValue . '" provided to show filter.');
        }
    }

    /**
     * Get the show filter name.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function getFilterName(): string
    {
        if ($this->functionName === null) {
            throw new InvalidValueException('Function name is required for show filters.');
        }

        $functionName = (string) $this->functionName;

        switch (strtolower($functionName)) {
            case 'categorylist_load_query':
                return 'Category_Show';
            case 'categoryproductlist_load_query':
            case 'productlist_load_query':
                return 'Product_Show';
        }

        throw new InvalidValueException('Show filter not found for function "' . $functionName . '".');
    }

    /**
     * Determine if a function name is valid for the show filter.
     */
    protected function isValidFunctionName(string $functionName): bool
    {
        return (in_array(strtolower($functionName), self::FUNCTION_NAMES));
    }

    /**
     * Determine if a show filter value is valid.
     */
    protected function isValidShowValue(string $showValue): bool
    {
        $showValues = [];

        if ($this->functionName === null) {
            return false;
        }

        $functionName = (string) $this->functionName;

        switch (strtolower($functionName)) {
            case 'categorylist_load_query':
                $showValues = ['active', 'all'];
                break;
            case 'categoryproductlist_load_query':
            case 'productlist_load_query':
                $showValues = ['active', 'all', 'uncategorized'];
                break;
        }

        if (empty($showValues)) {
            return false;
        }

        return in_array(strtolower($showValue), $showValues);
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): string
    {
        return $this->showValue;
    }
}
