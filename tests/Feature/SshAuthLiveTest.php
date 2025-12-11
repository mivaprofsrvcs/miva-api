<?php

declare(strict_types=1);

use pdeans\Miva\Api\Client;

it('performs a product list query using ssh authentication when configured', function (): void {
    if (! filter_var((string) env('MIVA_API_SSH_LIVE', ''), FILTER_VALIDATE_BOOL)) {
        test()->markTestSkipped('SSH live test disabled. Set MIVA_API_SSH_LIVE=true to run.');

        return;
    }

    $config = mivaSshClientConfig();

    if ($config === []) {
        return;
    }

    $client = new Client($config);

    $response = $client->func('ProductList_Load_Query')
        ->count(1)
        ->add()
        ->send();

    expect($response->successful())->toBeTrue();

    /** @var \stdClass $data */
    $data = $response->getData('ProductList_Load_Query');

    expect($data)->toBeInstanceOf(stdClass::class);
    expect($data->data)->toBeArray();
});
