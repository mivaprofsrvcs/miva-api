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
 * Build a client configuration array from environment variables
 * or skip tests when missing.
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
        test()->skip('Missing environment variables: ' . implode(', ', $missing));

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
 * Retrieve a JSON fixture by name for test helpers.
 */
function responseFixture(string $name, string $directory = 'Responses'): string
{
    $path = __DIR__ . '/Fixtures/' . trim($directory, '/ ') . '/' . $name . '.json';

    return (string) file_get_contents($path);
}
