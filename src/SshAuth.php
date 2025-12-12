<?php

declare(strict_types=1);

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Exceptions\InvalidValueException;

final class SshAuth
{
    /**
     * SSH authentication header name.
     */
    public const AUTH_HEADER_NAME = Auth::AUTH_HEADER_NAME;

    /**
     * Supported SSH algorithms.
     *
     * @var string[]
     */
    private const SUPPORTED_ALGORITHMS = ['sha256', 'sha512'];

    /**
     * @var callable(string, string, string): string|null
     */
    private $signer;

    /**
     * Create a new SSH authentication helper.
     *
     * @param string $username SSH username.
     * @param string $privateKey SSH private key contents.
     * @param string $algorithm Supported algorithm: sha256 or sha512.
     * @param callable(string, string, string): string|null $signer Custom signer callback for testing.
     */
    public function __construct(
        private readonly string $username,
        private readonly string $privateKey,
        private readonly string $algorithm = 'sha256',
        ?callable $signer = null
    ) {
        $this->validateAlgorithm($this->algorithm);

        $this->signer = $signer;
    }

    /**
     * Get the authorization header array.
     *
     * @return array<string, string>
     */
    public function getAuthHeader(string $body): array
    {
        return [self::AUTH_HEADER_NAME => $this->createAuthHeader($body)];
    }

    /**
     * Get the configured algorithm.
     */
    public function algorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Create the SSH authentication header value.
     */
    public function createAuthHeader(string $body): string
    {
        return sprintf(
            'SSH-RSA-%s %s:%s',
            $this->algorithm === 'sha512' ? 'SHA2-512' : 'SHA2-256',
            base64_encode($this->username),
            $this->createSignature($body)
        );
    }

    /**
     * Create the SSH signature.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    protected function createSignature(string $body): string
    {
        if ($this->signer !== null) {
            $signer = $this->signer;

            return base64_encode($signer($body, $this->privateKey, $this->algorithm));
        }

        $privateKey = openssl_pkey_get_private($this->privateKey);

        if ($privateKey === false) {
            throw new InvalidValueException('Invalid SSH private key provided.');
        }

        $opensslAlgorithm = $this->algorithm === 'sha512' ? OPENSSL_ALGO_SHA512 : OPENSSL_ALGO_SHA256;
        $signature = '';

        if (! openssl_sign($body, $signature, $privateKey, $opensslAlgorithm)) {
            throw new InvalidValueException('Unable to sign request with provided SSH private key.');
        }

        return base64_encode($signature);
    }

    /**
     * Get the configured username.
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * Validate algorithm value.
     */
    protected function validateAlgorithm(string $algorithm): void
    {
        if (! in_array($algorithm, self::SUPPORTED_ALGORITHMS, true)) {
            throw new InvalidValueException(
                'SSH authentication algorithm must be one of: "' . implode('", "', self::SUPPORTED_ALGORITHMS) . '".'
            );
        }
    }
}
