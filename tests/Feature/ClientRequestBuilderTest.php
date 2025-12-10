<?php

declare(strict_types=1);

use pdeans\Miva\Api\Client;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;
use Tests\Support\FakeGuzzleClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

it('builds iterations for a single function request', function (): void {
    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')
        ->count(2)
        ->add();

    $client->func('ProductList_Load_Query')
        ->count(5)
        ->add();

    $body = $client->getRequestBody(JSON_THROW_ON_ERROR);
    $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

    expect($payload['Store_Code'])->toBe('PS');
    expect($payload)->not->toHaveKey('Miva_Request_Timestamp');
    expect($payload['Function'])->toBe('ProductList_Load_Query');
    expect($payload['Iterations'])->toHaveCount(2);
    expect($payload['Iterations'][0]['Count'])->toBe(2);
    expect($payload['Iterations'][1]['Count'])->toBe(5);
});

it('builds operations for multiple functions', function (): void {
    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'timestamp' => false,
    ]);

    $client->func('CategoryList_Load_Query')
        ->count(1)
        ->add();

    $client->func('Product_Insert')
        ->add();

    $client->func('Product_Update')
        ->add();

    $body = $client->getRequestBody(JSON_THROW_ON_ERROR);
    $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->not->toHaveKey('Function');
    expect($payload['Store_Code'])->toBe('PS');
    expect($payload['Operations'])->toHaveCount(3);
    expect($payload['Operations'][0]['Function'])->toBe('CategoryList_Load_Query');
    expect($payload['Operations'][0]['Count'])->toBe(1);
    expect($payload['Operations'][1]['Function'])->toBe('Product_Insert');
    expect($payload['Operations'][2]['Function'])->toBe('Product_Update');
});

it('throws when required client options are missing', function (): void {
    expect(fn () => new Client([]))->toThrow(MissingRequiredValueException::class);

    expect(fn () => new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
    ]))->toThrow(MissingRequiredValueException::class);
});

it('merges custom headers and preserves defaults', function (): void {
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'http_client' => $guzzleMock,
        'http_headers' => [
            'X-Custom' => 'abc',
        ],
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    $headers = $guzzleMock->captured->getHeaders();

    expect($headers)->toHaveKey('Content-Type');
    expect($headers)->toHaveKey('Accept');
    expect($headers)->toHaveKey('User-Agent');
    expect($headers['X-Custom'][0])->toBe('abc');
    expect($headers)->toHaveKey('X-Miva-API-Authorization');
});

it('captures previous request and response after send', function (): void {
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    expect($client->getPreviousRequest())->toBeInstanceOf(RequestInterface::class);
    expect($client->getPreviousResponse())->toBeInstanceOf(ResponseInterface::class);
});
