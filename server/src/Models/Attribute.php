<?php
namespace Models;

use Config\Database;

abstract class Attribute
{
    protected string $name;
    protected string $type;
    protected array $items = [];
    protected Database $db;

    public function __construct(Database $db, string $name, string $type)
    {
        $this->db = $db;
        $this->name = $name;
        $this->type = $type;
    }

    public static function getByProductId(string $productId): array// Changed $productId type to string
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT attribute_id, name FROM attributes WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $productId]);

        $attributes = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $attributeItems = self::getAttributeItems($row['attribute_id']);
            $attributes[] = [
                'id' => $row['attribute_id'],
                'name' => $row['name'],
                'items' => $attributeItems,
            ];
        }
        return $attributes;
    }

    private static function getAttributeItems(int $attributeId): array
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT item_id, display_value, value FROM attribute_items WHERE attribute_id = :attribute_id");
        $stmt->execute(['attribute_id' => $attributeId]);

        $items = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = [
                'id' => $row['item_id'],
                'displayValue' => $row['display_value'],
                'value' => $row['value'],
            ];
        }
        return $items;
    }
}
