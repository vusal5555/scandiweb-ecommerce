<?php

namespace Models;

use Config\Database;

class ColorAttribute extends Attribute
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'Color', 'color');
    }

    public function loadItems(string $productId): array
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT attribute_id, name FROM attributes WHERE product_id = :product_id AND type = 'color'");
        $stmt->execute(['product_id' => $productId]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $attributes = [];
        foreach ($rows as $row) {
            $attributeItems = $this->getAttributeItems($row['attribute_id']);
            if (!empty($attributeItems)) {
                $attributes[] = [
                    'id' => $row['attribute_id'],
                    'name' => $row['name'],
                    'items' => $attributeItems,
                ];
            }
        }

        return $attributes;
    }
}
