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

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Exceptions\InvalidArgumentException;
use pdeans\Miva\Api\Exceptions\InvalidValueException;

class SearchFilterBuilder extends FilterBuilder
{
    /**
     * List of valid NULL search operators.
     *
     * @var array
     */
    protected const NULL_OPERATORS = [
        'TRUE',
        'FALSE',
        'NULL',
    ];

    /**
     * List of valid search operators.
     *
     * @var array
     */
    protected const OPERATORS = [
        'EQ',
        'GT',
        'GE',
        'LT',
        'LE',
        'CO',
        'NC',
        'LIKE',
        'NOTLIKE',
        'NE',
        'TRUE',
        'FALSE',
        'NULL',
        'IN',
        'SUBWHERE',
    ];

    /**
     * Filter search field.
     *
     * @var string
     */
    public string $field;

    /**
     * Filter search operator.
     *
     * @var string
     */
    public string $operator;

    /**
     * Filter search value.
     *
     * @var mixed
     */
    public mixed $value;

    /**
     * Create a new search filter builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(string $field, string $operator, mixed $value = null)
    {
        $this->field = trim($field);

        if ($this->field === '') {
            throw new InvalidValueException('Invalid value provided for "field".');
        }

        $this->operator = strtoupper($operator);

        if (!in_array($this->operator, self::OPERATORS)) {
            throw new InvalidValueException('Invalid operator "' . $operator . '" provided.');
        }

        if (is_null($value) && !in_array($this->operator, self::NULL_OPERATORS)) {
            throw new InvalidValueException('Invalid value provided for "value".');
        }

        $this->value = $value;
    }

    /**
     * Get the search null operators list.
     */
    public static function getNullOperators(): array
    {
        return self::NULL_OPERATORS;
    }

    /**
     * Get the search filter operator and value.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidArgumentException
     */
    public static function getOperatorAndValue(string $operator, mixed $value = null): array
    {
        $isNullOperator = in_array(strtoupper($operator), self::getNullOperators());

        if (is_null($value) && !$isNullOperator) {
            return ['EQ', $operator];
        }

        if ($isNullOperator) {
            return [$operator, null];
        }

        if (self::isInvalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Invalid operator and value search filter combination.');
        }

        return self::isInvalidOperator($operator) ? ['EQ', $operator] : [$operator, $value];
    }

    /**
     * Get the search operators list.
     */
    public static function getOperators(): array
    {
        return self::OPERATORS;
    }

    /**
     * Determine if a search operator is valid.
     */
    public static function isInvalidOperator(mixed $operator): bool
    {
        return ! is_string($operator) || ! in_array(strtoupper($operator), self::getOperators());
    }

    /**
     * Determine if search operator and value combination are valid.
     */
    public static function isInvalidOperatorAndValue(mixed $operator, mixed $value): bool
    {
        $operator = strtoupper($operator);

        return is_null($value)
            && in_array($operator, self::getOperators())
            && ! in_array($operator, self::getNullOperators());
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): array
    {
        $params = [
            'field' => $this->field,
            'operator' => $this->operator,
        ];

        if (! is_null($this->value)) {
            $params['value'] = $this->value;
        }

        return $params;
    }
}
