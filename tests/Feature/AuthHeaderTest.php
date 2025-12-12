<?php

declare(strict_types=1);

use pdeans\Miva\Api\Auth;
use pdeans\Miva\Api\Client;
use Tests\Support\FakeGuzzleClient;

it('builds an hmac sha256 authorization header for token auth', function (): void {
    $privateKey = base64_encode('secret-key');
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token-123',
        'private_key' => $privateKey,
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    $body = (string) ($guzzleMock->captured?->getBody() ?? '');
    $headers = $guzzleMock->captured?->getHeaders() ?? [];

    $signature = base64_encode(hash_hmac('sha256', $body, base64_decode($privateKey), true));
    $expected = 'MIVA-HMAC-SHA256 token-123:' . $signature;

    expect($headers[Auth::AUTH_HEADER_NAME][0] ?? null)->toBe($expected);
});

it('builds an hmac sha1 authorization header for token auth', function (): void {
    $privateKey = base64_encode('sha1-secret');
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token-456',
        'private_key' => $privateKey,
        'hmac' => 'sha1',
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    $body = (string) ($guzzleMock->captured?->getBody() ?? '');
    $headers = $guzzleMock->captured?->getHeaders() ?? [];

    $signature = base64_encode(hash_hmac('sha1', $body, base64_decode($privateKey), true));
    $expected = 'MIVA-HMAC-SHA1 token-456:' . $signature;

    expect($headers[Auth::AUTH_HEADER_NAME][0] ?? null)->toBe($expected);
});

it('uses the miva header format when hmac credentials are empty', function (): void {
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token-789',
        'private_key' => '',
        'hmac' => '',
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    $headers = $guzzleMock->captured?->getHeaders() ?? [];

    expect($headers[Auth::AUTH_HEADER_NAME][0] ?? null)->toBe('MIVA token-789');
});
