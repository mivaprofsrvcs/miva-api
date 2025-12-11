<?php

declare(strict_types=1);

use pdeans\Miva\Api\Client;
use pdeans\Miva\Api\Response as ApiResponse;
use Tests\Support\FakeGuzzleClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

it('returns a successful response for a product list query', function (): void {
    $functionName = 'ProductList_Load_Query';
    $config = mivaClientConfig();
    $config['http_client'] = new FakeGuzzleClient();

    $client = new Client($config);

    $client->func($functionName)
        ->count(1)
        ->add();

    $response = $client->send();

    expect($response)->toBeInstanceOf(ApiResponse::class);
    assert($response instanceof ApiResponse);

    expect($response->isSuccess())->toBeTrue();
    expect($response->getFunctions())->toContain($functionName);

    $function = $response->getFunction($functionName);

    expect($function)->toBeArray();
});

it('exposes previous request and response objects', function (): void {
    $config = mivaClientConfig();
    $config['http_client'] = new FakeGuzzleClient();

    $client = new Client($config);

    $client->func('ProductList_Load_Query')
        ->count(1)
        ->add();

    $client->send();

    expect($client->getPreviousRequest())->toBeInstanceOf(RequestInterface::class);
    expect($client->getPreviousResponse())->toBeInstanceOf(ResponseInterface::class);
});
