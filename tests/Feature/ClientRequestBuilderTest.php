<?php

declare(strict_types=1);

use pdeans\Miva\Api\Client;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;
use pdeans\Miva\Api\Auth;
use pdeans\Miva\Api\Response as ApiResponse;
use pdeans\Miva\Api\SshAuth;
use Tests\Support\FakeGuzzleClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

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

    expect($guzzleMock->captured)->toBeInstanceOf(RequestInterface::class);
    $headers = $guzzleMock->captured?->getHeaders() ?? [];

    expect($headers)->toHaveKey('Content-Type');
    expect($headers)->toHaveKey('Accept');
    expect($headers)->toHaveKey('User-Agent');
    expect($headers['X-Custom'][0])->toBe('abc');
    expect($headers)->toHaveKey(Auth::AUTH_HEADER_NAME);
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

it('adds timeout, binary encoding, and range headers when configured', function (): void {
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->setTimeout(100)
        ->setBinaryEncoding('base64')
        ->setOperationsRange(4, 5);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    $headers = $guzzleMock->captured?->getHeaders() ?? [];

    expect($headers['X-Miva-API-Timeout'][0] ?? null)->toBe('100');
    expect($headers['X-Miva-API-Binary-Encoding'][0] ?? null)->toBe('base64');
    expect($headers['Range'][0] ?? null)->toBe('Operations=4-5');
});

it('builds an SSH authentication header', function (): void {
    $guzzleMock = new FakeGuzzleClient();

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->setSshAuth('ssh-user', 'ssh-private-key', 'sha256');

    $client->func('ProductList_Load_Query')->count(1)->add();
    $client->send(true);

    $body = (string) ($guzzleMock->captured?->getBody() ?? '');
    $headers = $guzzleMock->captured?->getHeaders() ?? [];
    $signature = base64_encode(hash_hmac('sha256', $body, 'ssh-private-key', true));
    $expected = 'SSH-RSA-SHA2-256 ssh-user:' . $signature;

    expect($headers[SshAuth::AUTH_HEADER_NAME][0] ?? null)->toBe($expected);
});

it('parses partial responses with content range headers', function (): void {
    $guzzleMock = new FakeGuzzleClient(new GuzzleResponse(
        206,
        ['Content-Range' => '3/5'],
        '{"success":1,"data":{"total_count":0,"start_offset":0,"data":[]}}'
    ));

    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'http_client' => $guzzleMock,
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')->count(1)->add();
    $response = $client->send();

    assert($response instanceof ApiResponse);

    expect($response->getStatusCode())->toBe(206);
    expect($response->isPartial())->toBeTrue();
    expect($response->getContentRange())->toBe([
        'completed_operations' => 3,
        'total_operations' => 5,
    ]);
});

it('proxies function builder fluent methods through the client', function (): void {
    $client = new Client([
        'url' => 'https://example.test/mm5/json.mvc',
        'store_code' => 'PS',
        'access_token' => 'token',
        'private_key' => 'key',
        'timestamp' => false,
    ]);

    $client->func('ProductList_Load_Query')
        ->count(10)
        ->offset(5)
        ->sort('name')
        ->filter('search', ['field' => 'code', 'operator' => 'EQ', 'value' => 'SKU'])
        ->filters(['passphrase' => 'secret'])
        ->odc(['Code', 'Name'])
        ->params(['Custom' => 'Value'])
        ->passphrase('abc123')
        ->add();

    $payload = json_decode($client->getRequestBody(JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['Function'])->toBe('ProductList_Load_Query');
    expect($payload['Count'])->toBe(10);
    expect($payload['Offset'])->toBe(5);
    expect($payload['Sort'])->toBe('name');
    expect($payload['Filter'][0]['name'])->toBe('search');
    expect($payload['Filter'][1]['name'])->toBe('passphrase');
    expect($payload['Filter'][1]['value'])->toBe('secret');
    expect($payload['Filter'][2]['name'])->toBe('ondemandcolumns');
    expect($payload['Filter'][2]['value'])->toBe(['Code', 'Name']);
    expect($payload['Custom'])->toBe('Value');
    expect($payload['Passphrase'])->toBe('abc123');
});
