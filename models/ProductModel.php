<?php
/**
 * ProductModel — handles Product table operations
 */
class ProductModel extends Model
{
    /** Get all products with their category name */
    public function getAll(): array
    {
        return $this->db->read(
            "SELECT p.*, c.Name AS Category_Name
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             ORDER BY c.Name, p.Name"
        );
    }

    /** Get only active products */
    public function getActive(): array
    {
        return $this->db->read(
            "SELECT p.*, c.Name AS Category_Name
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE p.Active = 1
             ORDER BY c.Name, p.Name"
        );
    }

    /** Get active products grouped by category */
    public function getActiveGrouped(): array
    {
        $products = $this->getActive();
        $grouped = [];
        foreach ($products as $product) {
            $grouped[$product['Category_Name']][] = $product;
        }
        return $grouped;
    }

    /** Get active products as a flat JSON-safe array keyed by Product_ID */
    public function getActiveAsMap(): array
    {
        $products = $this->getActive();
        $map = [];
        foreach ($products as $p) {
            $map[$p['Product_ID']] = [
                'Product_ID'    => (int) $p['Product_ID'],
                'Name'          => $p['Name'],
                'Category_Name' => $p['Category_Name'],
                'Unit'          => $p['Unit'],
                'Price'         => (float) $p['Price'],
            ];
        }
        return $map;
    }

    /** Find a product by ID */
    public function findById(int $product_id): ?array
    {
        return $this->db->readOne(
            "SELECT p.*, c.Name AS Category_Name
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE p.Product_ID = ?",
            [$product_id]
        );
    }

    /** Create a new product */
    public function create(int $category_id, string $name, string $unit, float $price): int
    {
        return $this->db->insert(
            "INSERT INTO Product (Category_ID, Name, Unit, Price) VALUES (?, ?, ?, ?)",
            [$category_id, $name, $unit, $price]
        );
    }

    /** Update a product */
    public function update(int $product_id, int $category_id, string $name, string $unit, float $price, bool $active): int
    {
        return $this->db->write(
            "UPDATE Product SET Category_ID = ?, Name = ?, Unit = ?, Price = ?, Active = ? WHERE Product_ID = ?",
            [$category_id, $name, $unit, $price, (int) $active, $product_id]
        );
    }
}
