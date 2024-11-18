<?php
namespace Resolvers;

use Config\Database;
use Models\Order;

class MutationResolver
{

    public static function createOrder($root, $args, $context, $info)
    {
        $db = new Database(); // Create a Database instance
        $order = new Order($db); // Instantiate Order with the database connection
        $products = $args['products']; // Get products from GraphQL arguments

        $success = $order->create($products); // Pass the full product data to the create method
        return [
            'success' => $success,
            'message' => $success ? 'Order created successfully' : 'Failed to create order',
        ];
    }

}
