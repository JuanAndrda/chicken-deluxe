<?php
/**
 * CategoryModel — handles Category table operations
 */
class CategoryModel extends Model
{
    /** Get all categories */
    public function getAll(): array
    {
        return $this->db->read("SELECT * FROM Category ORDER BY Category_ID");
    }

    /** Get only active categories */
    public function getActive(): array
    {
        return $this->db->read("SELECT * FROM Category WHERE Active = 1 ORDER BY Name");
    }

    /** Find a category by ID */
    public function findById(int $category_id): ?array
    {
        return $this->db->readOne("SELECT * FROM Category WHERE Category_ID = ?", [$category_id]);
    }
}
