<?php

declare(strict_types=1);

/**
 * Retrieve environment variables with sensible fallbacks.
 */
function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return $value;
}

/**
 * Safely skip a test when prerequisites are missing.
 */
function skipTest(string $message): void
{
    $pending = test();

    if (method_exists($pending, 'skip')) {
        $pending->skip($message);
    }
}

/**
 * Build a client configuration array from environment variables
 * or skip tests when missing.
 *
 * @return array<string, mixed>
 */
function mivaClientConfig(): array
{
    $values = [
        'url' => (string) env('MIVA_API_URL', ''),
        'store_code' => (string) env('MIVA_API_STORE_CODE', ''),
        'access_token' => (string) env('MIVA_API_ACCESS_TOKEN', ''),
        'private_key' => (string) env('MIVA_API_PRIVATE_KEY', ''),
    ];

    $missing = array_keys(
        array_filter($values, static fn ($value) => $value === '')
    );

    if (! empty($missing)) {
        skipTest('Missing environment variables: ' . implode(', ', $missing));

        return [];
    }

    $headers = array_filter([
        'Authorization' => env('MIVA_API_HTTP_AUTH'),
        'Cache-Control' => env('MIVA_API_HTTP_CACHE'),
    ]);

    $extraHeaders = env('MIVA_API_HTTP_HEADERS');

    if (! empty($extraHeaders)) {
        $decodedHeaders = json_decode((string) $extraHeaders, true);

        if (is_array($decodedHeaders)) {
            foreach ($decodedHeaders as $name => $value) {
                if (! is_string($name) || $name === '') {
                    continue;
                }

                $headers[$name] = is_array($value)
                    ? json_encode($value)
                    : (string) $value;
            }
        }
    }

    $httpClientOptions = [];
    $httpClientVerify = env('MIVA_API_HTTP_VERIFY');

    if ($httpClientVerify !== false && $httpClientVerify !== '' && $httpClientVerify !== null) {
        $verify = filter_var($httpClientVerify, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($verify !== null) {
            $httpClientOptions['verify'] = $verify;
        }
    }

    $config = [
        'url' => $values['url'],
        'store_code' => $values['store_code'],
        'access_token' => $values['access_token'],
        'private_key' => $values['private_key'],
    ];

    if (! empty($httpClientOptions)) {
        $config['http_client'] = $httpClientOptions;
    }

    if (! empty($headers)) {
        $config['http_headers'] = $headers;
    }

    return $config;
}

/**
 * Build an SSH authenticated client configuration array or skip when missing.
 *
 * @return array<string, mixed>
 */
function mivaSshClientConfig(): array
{
    $values = [
        'url' => (string) env('MIVA_API_URL', ''),
        'store_code' => (string) env('MIVA_API_STORE_CODE', ''),
        'username' => (string) env('MIVA_API_SSH_USERNAME', ''),
        'private_key' => '',
    ];

    $privateKeyPath = (string) env('MIVA_API_SSH_PRIVATE_KEY_PATH', '');

    if ($privateKeyPath === '') {
        skipTest('Missing SSH private key path: set MIVA_API_SSH_PRIVATE_KEY_PATH.');

        return [];
    }

    $values['private_key'] = $privateKeyPath;

    $headers = array_filter([
        'Authorization' => env('MIVA_API_HTTP_AUTH'),
        'Cache-Control' => env('MIVA_API_HTTP_CACHE'),
    ]);

    $extraHeaders = env('MIVA_API_HTTP_HEADERS');

    if (! empty($extraHeaders)) {
        $decodedHeaders = json_decode((string) $extraHeaders, true);

        if (is_array($decodedHeaders)) {
            foreach ($decodedHeaders as $name => $value) {
                if (! is_string($name) || $name === '') {
                    continue;
                }

                $headers[$name] = is_array($value)
                    ? json_encode($value)
                    : (string) $value;
            }
        }
    }

    $missing = array_keys(
        array_filter($values, static fn ($value) => $value === '')
    );

    if (! empty($missing)) {
        skipTest('Missing environment variables: ' . implode(', ', $missing));

        return [];
    }

    $httpClient = [
        'verify' => false,
    ];

    $verifySetting = env('MIVA_API_HTTP_VERIFY');
    $verify = filter_var((string) $verifySetting, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

    if ($verify !== null) {
        $httpClient['verify'] = $verify;
    }

    $config = [
        'url' => $values['url'],
        'store_code' => $values['store_code'],
        'ssh_auth' => [
            'username' => $values['username'],
            'private_key' => $values['private_key'],
            'algorithm' => (string) env('MIVA_API_SSH_ALGORITHM', 'sha256'),
        ],
        'http_client' => $httpClient,
    ];

    if (! empty($headers)) {
        $config['http_headers'] = $headers;
    }

    return $config;
}

/**
 * Retrieve a JSON fixture by name for test helpers.
 */
function responseFixture(string $name, string $directory = 'Responses'): string
{
    $path = __DIR__ . '/Fixtures/' . trim($directory, '/ ') . '/' . $name . '.json';

    return (string) file_get_contents($path);
}
