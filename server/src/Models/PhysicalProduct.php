<?php
namespace Models;

use Config\Database;

class PhysicalProduct extends Product
{
    public function __construct(array $data, Database $db) // Ensure Database is type-hinted as Config\Database
    {
        parent::__construct($data, $db);
    }

    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'in_stock' => $this->inStock,
            'gallery' => $this->gallery,
            'description' => $this->description,
            'category' => $this->category,
            'brand' => $this->brand,
            'prices' => $this->prices,
            // Return an empty array if attributes contain only null values
            'attributes' => array_filter($this->attributes, ) ?: [],
        ];
    }
}
