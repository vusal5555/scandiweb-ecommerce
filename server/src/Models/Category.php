<?php
namespace Models;

use Config\Database;
use PDO;

class Category
{
    protected ?int $id;
    protected ?string $name;

    public function __construct(?int $id, ?string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    // Method to retrieve all categories from the database
    public static function getAll(): array
    {
        // Get a database connection
        $db = new Database();
        $conn = $db->getConnection();

        // Prepare and execute the query
        $stmt = $conn->prepare("SELECT * FROM categories");
        $stmt->execute();

        // Fetch all rows as associative arrays
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map results to Category objects, handling missing data gracefully
        return array_map(function ($categoryData) {
            return new Category(
                $categoryData['id'] ?? null,
                $categoryData['name'] ?? 'Unnamed Category'
            );
        }, $categories);
    }

    // Method to retrieve category details
    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
