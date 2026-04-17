<?php
/**
 * RoleModel — handles Role table operations
 */
class RoleModel extends Model
{
    /** Get all roles */
    public function getAll(): array
    {
        return $this->db->read("SELECT * FROM Role ORDER BY Role_ID");
    }

    /** Find a role by ID */
    public function findById(int $role_id): ?array
    {
        return $this->db->readOne("SELECT * FROM Role WHERE Role_ID = ?", [$role_id]);
    }
}
