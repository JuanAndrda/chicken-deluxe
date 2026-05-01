<?php
/**
 * PartModel — handles Part table operations.
 *
 * Parts are raw ingredients (Burger Bun, Patty, Cheese, etc.) used to
 * build finished Products. Inventory and Delivery now track parts
 * instead of products; sales auto-deduct parts via the Product_Part
 * recipe junction.
 */
class PartModel extends Model
{
    /** Get every part (active + inactive) */
    public function getAll(): array
    {
        return $this->db->read(
            "SELECT * FROM Part ORDER BY Name"
        );
    }

    /** Get only active parts */
    public function getActive(): array
    {
        return $this->db->read(
            "SELECT * FROM Part WHERE Active = 1 ORDER BY Name"
        );
    }

    /** Find a single part by its ID */
    public function findById(int $part_id): ?array
    {
        return $this->db->readOne(
            "SELECT * FROM Part WHERE Part_ID = ?",
            [$part_id]
        );
    }

    /** Create a new part — returns new Part_ID */
    public function create(string $name, string $unit): int
    {
        return $this->db->insert(
            "INSERT INTO Part (Name, Unit) VALUES (?, ?)",
            [$name, $unit]
        );
    }

    /** Update an existing part (rename / change unit / soft-delete) */
    public function update(int $part_id, string $name, string $unit, bool $active): int
    {
        return $this->db->write(
            "UPDATE Part SET Name = ?, Unit = ?, Active = ? WHERE Part_ID = ?",
            [$name, $unit, (int) $active, $part_id]
        );
    }

    /** Active parts as a flat map keyed by Part_ID — useful for JS */
    public function getActiveAsMap(): array
    {
        $parts = $this->getActive();
        $map = [];
        foreach ($parts as $p) {
            $map[(int) $p['Part_ID']] = [
                'Part_ID' => (int) $p['Part_ID'],
                'Name'    => $p['Name'],
                'Unit'    => $p['Unit'],
            ];
        }
        return $map;
    }
}
