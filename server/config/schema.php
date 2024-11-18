<?php
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;

$priceType = new ObjectType([
    'name' => 'Price',
    'fields' => [
        'amount' => Type::float(),
        'currency' => [
            'type' => new ObjectType([
                'name' => 'Currency',
                'fields' => [
                    'label' => Type::string(),
                    'symbol' => Type::string(),
                ],
            ]),
        ],
    ],
]);

// $attributeType = new ObjectType([
//     'name' => 'Attribute',
//     'fields' => [
//         'id' => Type::id(),
//         'name' => Type::string(),
//         'items' => Type::listOf(new ObjectType([
//             'name' => 'AttributeItem',
//             'fields' => [
//                 'displayValue' => Type::string(),
//                 'value' => Type::string(),
//                 'id' => Type::id(),
//             ],
//         ])),
//     ],
// ]);

$attributeType = new ObjectType([
    'name' => 'Attribute',
    'fields' => [
        'id' => Type::string(),
        'name' => Type::string(),
        'items' => Type::listOf(new ObjectType([
            'name' => 'AttributeItem',
            'fields' => [
                'displayValue' => Type::string(),
                'value' => Type::string(),
                'id' => Type::string(),
            ],
        ])),
    ],
]);

// $productType = new ObjectType([
//     'name' => 'Product',
//     'fields' => [
//         'id' => Type::id(),
//         'name' => Type::string(),
//         'in_stock' => Type::boolean(),
//         'gallery' => Type::listOf(Type::string()),
//         'description' => Type::string(),
//         'category' => Type::string(),
//         'attributes' => Type::listOf($attributeType),
//         'prices' => Type::listOf($priceType),
//         'brand' => Type::string(),
//         'quantity' => Type::int(),
//     ],
// ]);

$productType = new ObjectType([
    'name' => 'Product',
    'fields' => [
        'id' => Type::id(),
        'name' => Type::string(),
        'in_stock' => Type::boolean(),
        'gallery' => Type::listOf(Type::string()),
        'description' => Type::string(),
        'category' => Type::string(),
        'attributes' => Type::listOf($attributeType), // Correctly defined as a list
        'prices' => Type::listOf($priceType),
        'brand' => Type::string(),
        'quantity' => Type::int(), // Quantity for the order product
    ],
]);

$categoryType = new ObjectType([
    'name' => 'Category',
    'fields' => [
        'id' => Type::id(),
        'name' => Type::string(),

    ],
]);

// $orderType = new ObjectType([
//     'name' => 'Order',
//     'fields' => [
//         'id' => Type::id(),
//         'created_at' => Type::string(), // Format as needed
//         'products' => [
//             'type' => Type::listOf($productType),
//             'resolve' => function ($order, $args) {
//                 // Create an instance of Order and load the products
//                 $db = new \Config\Database();
//                 $orderInstance = new \Models\Order($db);

//                 $orderInstance->loadById($order['id']);
//                 return $orderInstance->getProducts();
//             },
//         ],
//     ],
// ]);

$orderType = new ObjectType([
    'name' => 'Order',
    'fields' => [
        'id' => Type::id(),
        'created_at' => Type::string(), // Format as needed
        'products' => [
            'type' => Type::listOf($productType),
            'resolve' => function ($order, $args) {
                // Create an instance of Order and load the products
                $db = new \Config\Database();
                $orderInstance = new \Models\Order($db);

                $orderInstance->loadById($order['id']);
                return $orderInstance->getProducts();
            },
        ],
    ],
]);

$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'products' => [
            'type' => Type::listOf($productType),
            'resolve' => ['Resolvers\QueryResolver', 'products'],
        ],
        'categories' => [
            'type' => Type::listOf($categoryType),
            'resolve' => ['Resolvers\QueryResolver', 'categories'],
        ],
        'product' => [
            'type' => $productType,
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            'resolve' => ['Resolvers\QueryResolver', 'product'],
        ],
        'orders' => [
            'type' => Type::listOf($orderType),
            'resolve' => ['Resolvers\QueryResolver', 'orders'],
        ],
        'order' => [
            'type' => $orderType,
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            'resolve' => ['Resolvers\QueryResolver', 'order'],
        ],
    ],
]);

$orderResponseType = new ObjectType([
    'name' => 'OrderResponse',
    'fields' => [
        'success' => Type::boolean(),
        'message' => Type::string(),
    ],
]);

$productInputType = new InputObjectType([
    'name' => 'ProductInput',
    'fields' => [
        'id' => Type::nonNull(Type::id()),
        'quantity' => Type::int(),
        'attributes' => Type::string(), // JSON string representation of attributes
    ],
]);

$mutationType = new ObjectType([
    'name' => 'Mutation',
    'fields' => [
        'createOrder' => [
            'type' => $orderResponseType,
            'args' => [
                'products' => Type::listOf($productInputType), // Use the custom ProductInput type
            ],
            'resolve' => ['Resolvers\MutationResolver', 'createOrder'],
        ],
    ],
]);

return new Schema(
    SchemaConfig::create()
        ->setQuery($queryType)
        ->setMutation($mutationType)
);
