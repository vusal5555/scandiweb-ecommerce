<?php

use Config\Database;

// Load JSON data
$json = file_get_contents('data.json');
if ($json === false) {
    die("Error: Unable to load data.json");
}

$data = json_decode($json, true);
if ($data === null) {
    die("Error: JSON data could not be decoded. Please check data.json format.");
}

// Database connection
$db = new Database();
$conn = $db->getConnection();

// Clear existing data
$conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
$conn->exec("TRUNCATE TABLE attribute_items;");
$conn->exec("TRUNCATE TABLE attributes;");
$conn->exec("TRUNCATE TABLE prices;");
$conn->exec("TRUNCATE TABLE product_galleries;");
$conn->exec("TRUNCATE TABLE products;");
$conn->exec("TRUNCATE TABLE categories;");
$conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Insert categories
if (isset($data['data']['categories'])) {
    foreach ($data['data']['categories'] as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->execute(['name' => $category['name']]);
    }
}

// Insert products and related data
foreach ($data['data']['products'] as $product) {
    $stmt = $conn->prepare("INSERT INTO products (id, name, in_stock, description, category, brand) VALUES (:id, :name, :in_stock, :description, :category, :brand)");
    $stmt->execute([
        'id' => $product['id'],
        'name' => $product['name'],
        'in_stock' => $product['inStock'] ? 1 : 0,
        'description' => $product['description'],
        'category' => $product['category'],
        'brand' => $product['brand'],
    ]);

    // Insert product galleries
    if (isset($product['gallery']) && is_array($product['gallery'])) {
        foreach ($product['gallery'] as $image_url) {
            $stmt = $conn->prepare("INSERT INTO product_galleries (product_id, image_url) VALUES (:product_id, :image_url)");
            $stmt->execute([
                'product_id' => $product['id'],
                'image_url' => $image_url,
            ]);
        }
    }

    // Insert product prices
    foreach ($product['prices'] as $price) {
        $stmt = $conn->prepare("INSERT INTO prices (product_id, amount, currency_label, currency_symbol) VALUES (:product_id, :amount, :currency_label, :currency_symbol)");
        $stmt->execute([
            'product_id' => $product['id'],
            'amount' => $price['amount'],
            'currency_label' => $price['currency']['label'],
            'currency_symbol' => $price['currency']['symbol'],
        ]);
    }

    // Insert product attributes
    foreach ($product['attributes'] as $attribute) {
        // Insert the attribute and get the last inserted ID
        $stmt = $conn->prepare("INSERT INTO attributes (product_id, name, type) VALUES (:product_id, :name, :type)");
        $stmt->execute([
            'product_id' => $product['id'],
            'name' => $attribute['name'],
            'type' => $attribute['type'],
        ]);
        $attribute_id = $conn->lastInsertId();

        // Insert attribute items
        foreach ($attribute['items'] as $item) {
            $stmt = $conn->prepare("INSERT INTO attribute_items (attribute_id, display_value, value, item_id) VALUES (:attribute_id, :display_value, :value, :item_id)");
            $stmt->execute([
                'attribute_id' => $attribute_id,
                'display_value' => $item['displayValue'],
                'value' => $item['value'],
                'item_id' => $item['id'],
            ]);
        }
    }
}

echo "Data imported successfully.";
