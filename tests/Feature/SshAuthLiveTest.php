<?php

declare(strict_types=1);

use pdeans\Miva\Api\Client;
use pdeans\Miva\Api\Response;

// Only register this live test when explicitly enabled.
if (! filter_var((string) env('MIVA_API_SSH_LIVE', ''), FILTER_VALIDATE_BOOL)) {
    return;
}

it('performs a product list query using ssh authentication when configured', function (): void {
    $config = mivaSshClientConfig();

    if ($config === []) {
        return;
    }

    $client = new Client($config);

    $response = $client->func('ProductList_Load_Query')
        ->count(1)
        ->add()
        ->send();

    assert($response instanceof Response);

    expect($response->successful())->toBeTrue();

    /** @var \stdClass $data */
    $data = $response->getData('ProductList_Load_Query');

    expect($data)->toBeInstanceOf(stdClass::class);
    expect($data->data)->toBeArray();
});
