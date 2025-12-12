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

use pdeans\Miva\Api\Exceptions\InvalidValueException;

final class Auth
{
    /**
     * API authentication header name.
     */
    public const AUTH_HEADER_NAME = 'X-Miva-API-Authorization';

    /**
     * List of valid HMAC types.
     *
     * @var string[]
     */
    private const HMAC_LIST = ['sha1', 'sha256'];

    /**
     * API access token.
     *
     * @var string
     */
    private string $accessToken;

    /**
     * API request HMAC signature.
     *
     * @var string
     */
    private string $hmacSignature = '';

    /**
     * The HMAC type.
     *
     * @var string
     */
    private string $hmacType;

    /**
     * API private key.
     *
     * @var string
     */
    private string $privateKey;

    /**
     * Create a new API auth instance.
     */
    public function __construct(string $accessToken, string $privateKey, string $hmacType = 'sha256')
    {
        $this->accessToken = $accessToken;
        $this->privateKey = $privateKey;

        $this->setHmacType($hmacType);
    }

    /**
     * Create an API authorization header.
     */
    public function createAuthHeader(string $data): string
    {
        return sprintf('%s: %s', self::AUTH_HEADER_NAME, $this->createAuthHeaderValue($data));
    }

    /**
     * Create an API authorization header value.
     */
    public function createAuthHeaderValue(string $data): string
    {
        if ((string) $this->hmacType === '') {
            return sprintf('MIVA %s', $this->accessToken);
        }

        $this->hmacSignature = $this->createHmacSignature($data);

        return sprintf(
            'MIVA-HMAC-%s %s:%s',
            strtoupper($this->hmacType),
            $this->accessToken,
            base64_encode($this->hmacSignature)
        );
    }

    /**
     * Generate a keyed hash value using the HMAC type.
     */
    protected function createHmacSignature(string $data): string
    {
        return hash_hmac($this->hmacType, $data, base64_decode($this->privateKey), true);
    }

    /**
     * Get the API authorization header.
     *
     * @return array<string, string>
     */
    public function getAuthHeader(string $data): array
    {
        return [self::AUTH_HEADER_NAME => $this->createAuthHeaderValue($data)];
    }

    /**
     * Set the HMAC type.
     */
    protected function setHmacType(string $hmacType): static
    {
        if ($hmacType === '' || $this->privateKey === '') {
            $this->hmacType = '';
        } else {
            $hmacTypeFormatted = strtolower($hmacType);

            if (! in_array($hmacTypeFormatted, self::HMAC_LIST)) {
                throw new InvalidValueException(
                    sprintf(
                        'Invalid HMAC type "%s" provided. Valid HMAC types: "%s".',
                        $hmacType,
                        implode('", "', self::HMAC_LIST)
                    )
                );
            }

            $this->hmacType = $hmacTypeFormatted;
        }

        return $this;
    }
}
