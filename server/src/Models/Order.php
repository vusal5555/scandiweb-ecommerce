<?php

namespace Models;

use Config\Database;
use PDO;

class Order
{
    private $id;
    private $createdAt;
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db->getConnection();
    }

    public function create(array $products): bool
    {
        if (empty($products)) {
            throw new \InvalidArgumentException("Products array cannot be empty.");
        }

        try {
            $this->db->beginTransaction();

            // Insert order and capture the order ID
            $stmt = $this->db->prepare("INSERT INTO orders (created_at) VALUES (NOW())");
            $stmt->execute();
            $this->id = $this->db->lastInsertId();

            // Prepare bulk insert for related products
            foreach ($products as $product) {
                if (!isset($product['id'], $product['quantity'])) {
                    throw new \InvalidArgumentException("Each product must have 'id' and 'quantity'.");
                }

                $attributes = isset($product['attributes']) && $product['attributes'] !== null
                ? json_encode($product['attributes'])
                : null;

                // Check if product already exists in order_products
                $stmt = $this->db->prepare("SELECT * FROM order_products WHERE order_id = ? AND product_id = ?");
                $stmt->execute([$this->id, $product['id']]);
                $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingProduct) {
                    // Product already exists, update the quantity
                    $newQuantity = $existingProduct['quantity'] + $product['quantity'];
                    $updateStmt = $this->db->prepare("UPDATE order_products SET quantity = ? WHERE order_id = ? AND product_id = ?");
                    $updateStmt->execute([$newQuantity, $this->id, $product['id']]);
                } else {
                    // Product doesn't exist, insert a new entry
                    $stmt = $this->db->prepare("INSERT INTO order_products (order_id, product_id, quantity, attributes) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$this->id, $product['id'], $product['quantity'], $attributes]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }

    public function getProducts(): array
    {
        $stmt = $this->db->prepare("
        SELECT
            op.product_id AS id,
            op.quantity,
            op.attributes,
            p.name,
            p.description
        FROM order_products op
        JOIN products p ON op.product_id = p.id
        WHERE op.order_id = ?
    ");
        $stmt->execute([$this->id]);

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($product) {
            // Handle attributes
            if (!empty($product['attributes'])) {
                // Remove unnecessary escaping
                $cleanedAttributes = stripcslashes(trim($product['attributes'], '"'));
                $decodedAttributes = json_decode($cleanedAttributes, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $product['attributes'] = $decodedAttributes;
                } else {
                    $product['attributes'] = []; // Fallback to empty array if decoding fails
                    error_log("Failed to decode attributes: " . $product['attributes']);
                }
            } else {
                $product['attributes'] = []; // Handle null or empty attributes
            }

            // Debugging logs
            error_log('Raw attributes: ' . (is_string($product['attributes']) ? $product['attributes'] : json_encode($product['attributes'])));
            error_log('Decoded attributes: ' . print_r($product['attributes'], true));

            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'quantity' => $product['quantity'],
                'attributes' => is_array($product['attributes'])
                ? $this->formatAttributes($product['attributes'])
                : [],
                'prices' => $this->getPrices($product['id'], $product['quantity']), // Pass quantity
            ];
        }, $products);
    }

    private function formatAttributes(array $attributes): array
    {

        // Check if attributes is not null or empty before decoding

        $formattedAttributes = array_map(function ($key, $value) {
            return [
                'id' => strtolower(str_replace(' ', '_', $key)),
                'name' => $key,
                'items' => [
                    [
                        'displayValue' => $value,
                        'value' => strtolower($value),
                        'id' => strtolower(str_replace(' ', '_', $value)),
                    ],
                ],
            ];
        }, array_keys($attributes), $attributes);

        error_log('Formatted attributes: ' . print_r($formattedAttributes, true)); // Debug log
        return $formattedAttributes;
    }

    private function getPrices(string $productId, int $quantity): array
    {
        $stmt = $this->db->prepare("
        SELECT
            amount,
            currency_label AS label,
            currency_symbol AS symbol
        FROM prices
        WHERE product_id = ?
    ");
        $stmt->execute([$productId]);

        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map the results to include the currency structure and calculate the total price
        return array_map(function ($price) use ($quantity) {
            return [
                'amount' => round($price['amount'] * $quantity, 2), // Use round instead of number_format
                'currency' => [
                    'label' => $price['label'],
                    'symbol' => $price['symbol'],
                ],
            ];
        }, $prices);
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders ORDER BY created_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function loadById(int $id): ?Order
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Populate object properties
        $this->id = $order['id'];
        $this->createdAt = $order['created_at'];

        return $this;
    }

    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'products' => $this->getProducts(),
        ];
    }
}
