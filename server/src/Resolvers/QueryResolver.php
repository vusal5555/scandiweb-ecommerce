<?php
namespace Resolvers;

use Config\Database;
use Models\Attribute;
use Models\Category;
use Models\ColorAttribute;
use Models\Order;
use Models\Price;
use Models\Product;

class QueryResolver
{
    public static function products()
    {
        $db = new Database();
        $products = Product::getAll($db);

        foreach ($products as $product) {
            $product->setPrices(Price::getByProductId($product->getId()));
            $colorAttribute = new ColorAttribute($db);
            $product->setAttributes(array_merge($product->getAttributes(), [$colorAttribute->loadItems($product->getId())]));
        }

        return array_map(fn($product) => $product->getDetails(), $products);
    }

    public static function categories()
    {
        $categories = Category::getAll();
        return array_map(fn($category) => $category->getDetails(), $categories);
    }

    public static function product($root, $args, $context, $info)
    {
        $db = new Database();
        $product = Product::getById($db, $args['id']);

        if (!$product) {
            return null;
        }

        $product->setPrices(Price::getByProductId($product->getId()));
        $product->setAttributes(Attribute::getByProductId($product->getId()));

        return $product->getDetails();
    }

    // public static function orders()
    // {
    //     $db = new Database();
    //     $orderModel = new Order($db);

    //     // Fetch all orders and map them to details
    //     $orders = $orderModel->getAll();
    //     return array_map(fn($order) => $orderModel->loadById($order['id'])->getDetails(), $orders);
    // }

    public static function orders()
    {
        $db = new Database();
        $orderModel = new Order($db);

        // Fetch all orders and include their detailed information
        $orders = $orderModel->getAll();
        return array_map(fn($order) => $orderModel->loadById($order['id'])->getDetails(), $orders);
    }

    // public static function order($root, $args, $context, $info)
    // {
    //     $db = new Database();
    //     $order = new Order($db);
    //     $order->loadById($args['id']);

    //     return $order->getDetails();
    // }
    public static function order($root, $args, $context, $info)
    {
        $db = new Database();
        $order = new Order($db);

        if (!$order->loadById($args['id'])) {
            return null; // Return null if the order doesn't exist
        }

        return $order->getDetails();
    }

}
