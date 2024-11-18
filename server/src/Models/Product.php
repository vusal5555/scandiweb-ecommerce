<?php
namespace Models;

use Config\Database;
use PDO;

abstract class Product
{
    protected string $id;
    protected string $name;
    protected bool $inStock;
    protected array $gallery = [];
    protected string $description;
    protected string $category;
    protected string $brand;
    protected array $attributes = [];
    protected array $prices = [];
    protected Database $db;

    public function __construct(array $data, Database $db)
    {
        $this->db = $db;
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->inStock = (bool) $data['in_stock'];
        $this->gallery = $this->fetchGallery();
        $this->description = $data['description'];
        $this->category = $data['category'];
        $this->brand = $data['brand'];
        $this->attributes = $this->fetchAttributes();
        $this->prices = $this->fetchPrices();
    }

    abstract public function getDetails(): array;

    public function getId(): string
    {
        return $this->id;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }

    public function setPrices(array $prices): void
    {
        $this->prices = $prices;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    protected function fetchGallery(): array
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT image_url FROM product_galleries WHERE product_id = ?");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function fetchAttributes(): array
    {
        return Attribute::getByProductId($this->id);
    }

    protected function fetchPrices(): array
    {
        return Price::getByProductId($this->id);
    }

    public static function getAll(Database $db): array
    {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM products");
        $stmt->execute();
        $productsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $products = [];
        foreach ($productsData as $productData) {
            $products[] = new PhysicalProduct($productData, $db); // Instantiate as PhysicalProduct
        }

        return $products;
    }

    public static function getById(Database $db, string $id): ?Product
    {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $productData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productData) {
            return null; // Product not found
        }

        return new PhysicalProduct($productData, $db); // Instantiate as PhysicalProduct
    }
}
