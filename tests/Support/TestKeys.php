<?php

declare(strict_types=1);

namespace Tests\Support;

final class TestKeys
{
    /**
     * Get a test RSA private key for signing.
     */
    public static function sshPrivateKey(): string
    {
        return (string) file_get_contents(__DIR__ . '/../Fixtures/Keys/test_rsa_private.pem');
    }

    /**
     * Sign data using OpenSSL with the given private key.
     */
    public static function signWithPrivateKey(string $data, string $privateKey, string $algorithm): string
    {
        $key = openssl_pkey_get_private($privateKey);

        if ($key === false) {
            return '';
        }

        $algo = $algorithm === 'sha512' ? OPENSSL_ALGO_SHA512 : OPENSSL_ALGO_SHA256;
        $signature = '';

        openssl_sign($data, $signature, $key, $algo);

        return base64_encode($signature);
    }
}
