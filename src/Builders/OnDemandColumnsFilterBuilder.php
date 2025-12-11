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

class OnDemandColumnsFilterBuilder extends FilterBuilder
{
    /**
     * On-demand columns list
     *
     * @var array<int, string>
     */
    public array $columns;

    /**
     * Create a new on-demand columns filter builder instance.
     *
     * @param array<int, string> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = array_map('strval', $columns);
    }

    /**
     * Define JSON serialization format.
     *
     * @return array<int, string>
     */
    public function jsonSerialize(): array
    {
        return $this->columns;
    }
}
