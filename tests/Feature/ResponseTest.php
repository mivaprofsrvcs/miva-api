<?php

declare(strict_types=1);

use pdeans\Miva\Api\Response;

it('parses a successful list load query response', function (): void {
    $response = new Response(
        ['ProductList_Load_Query'],
        responseFixture('product_list_load_query_success')
    );

    expect($response->successful())->toBeTrue();
    expect($response->hasErrors())->toBeFalse();
    expect($response->getFunctions())->toContain('ProductList_Load_Query');

    /** @var \stdClass $data */
    $data = $response->getData('ProductList_Load_Query');

    expect($data->total_count)->toBe(117);
    expect($data->data)->toHaveCount(2);
    expect($data->data[0]->code)->toBe('prod1');
});

it('parses a successful list load response', function (): void {
    $response = new Response(
        ['OrderCustomFieldList_Load'],
        responseFixture('order_custom_field_list_load_success')
    );

    expect($response->successful())->toBeTrue();
    expect($response->hasErrors())->toBeFalse();
    /** @var array<int, \stdClass> $data */
    $data = $response->getData('OrderCustomFieldList_Load');

    expect($data)->toBeArray();
    expect($data)->not()->toBeEmpty();
    expect($data[0]->code)->toBe('attrqueue');
});

it('parses iteration responses for a single function', function (): void {
    $response = new Response(
        ['Product_Insert' => [1, 1]],
        responseFixture('product_insert_iterations_success')
    );

    expect($response->successful())->toBeTrue();
    expect($response->getFunction('Product_Insert'))->toHaveCount(2);
    /** @var \stdClass $insertData */
    $insertData = $response->getData('Product_Insert', 1);
    expect($insertData->code)->toBe('new-product-2');
});

it('parses single insert response', function (): void {
    $response = new Response(
        ['Product_Insert'],
        responseFixture('product_insert_success')
    );

    expect($response->successful())->toBeTrue();
    /** @var \stdClass $insert */
    $insert = $response->getData('Product_Insert');
    expect($insert->code)->toBe('new-product');
});

it('captures a single function error response', function (): void {
    $response = new Response(
        ['CategoryList_Load_Query'],
        responseFixture('error_invalid_function')
    );

    expect($response->failed())->toBeTrue();
    expect($response->hasErrors())->toBeTrue();
    expect($response->errors()->all())->toHaveCount(1);
    expect($response->errors()->messages())->toContain('Invalid function');
});

it('captures iteration level validation errors', function (): void {
    $response = new Response(
        ['Product_Update' => [1, 1]],
        responseFixture('product_update_iterations_error_validation')
    );

    expect($response->failed())->toBeFalse();
    expect($response->hasErrors())->toBeTrue();
    expect($response->errors()->all())->toHaveCount(1);
    expect($response->errors()->forField('Product_Price'))->toHaveCount(1);
    expect($response->errors()->messages())->toContain('One or more parameters are invalid');
});

it('parses mixed operations with iterations and errors', function (): void {
    $response = new Response(
        [
            'CategoryList_Load_Query' => [1],
            'Product_Insert' => [1],
            'Product_Update' => [1, 1],
        ],
        responseFixture('operations_mixed_errors')
    );

    expect($response->failed())->toBeFalse();
    expect($response->hasErrors())->toBeTrue();
    expect($response->errors()->all())->toHaveCount(2);
    expect($response->errors()->forField('Product_Code'))->toHaveCount(1);
    expect($response->errors()->forField('Product_Price'))->toHaveCount(1);
});

it('parses mixed operations with iterations and successes', function (): void {
    $response = new Response(
        [
            'CategoryList_Load_Query' => [1],
            'ProductList_Load_Query' => [1, 1],
            'Product_Insert' => [1],
            'Product_Update' => [1, 1],
        ],
        responseFixture('operations_mixed_success')
    );

    expect($response->successful())->toBeTrue();
    expect($response->hasErrors())->toBeFalse();
    expect($response->getFunction('ProductList_Load_Query'))->toHaveCount(2);
});

it('parses content range header from partial responses', function (): void {
    $response = new Response(
        ['ProductList_Load_Query'],
        responseFixture('product_list_load_query_success'),
        206,
        ['Content-Range' => ['3/5']]
    );

    expect($response->isPartial())->toBeTrue();
    expect($response->getStatusCode())->toBe(206);
    expect($response->getContentRange())->toBe([
        'completed_operations' => 3,
        'total_operations' => 5,
    ]);
});
