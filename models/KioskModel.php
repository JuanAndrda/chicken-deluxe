<?php
/**
 * KioskModel — handles Kiosk/Outlet table operations
 */
class KioskModel extends Model
{
    /** Get all kiosks */
    public function getAll(): array
    {
        return $this->db->read("SELECT * FROM Kiosk ORDER BY Kiosk_ID");
    }

    /** Get only active kiosks */
    public function getActive(): array
    {
        return $this->db->read("SELECT * FROM Kiosk WHERE Active = 1 ORDER BY Kiosk_ID");
    }

    /** Find a kiosk by ID */
    public function findById(int $kiosk_id): ?array
    {
        return $this->db->readOne("SELECT * FROM Kiosk WHERE Kiosk_ID = ?", [$kiosk_id]);
    }

    /** Create a new kiosk */
    public function create(string $name, string $location): int
    {
        return $this->db->insert(
            "INSERT INTO Kiosk (Name, Location) VALUES (?, ?)",
            [$name, $location]
        );
    }

    /** Update a kiosk */
    public function update(int $kiosk_id, string $name, string $location, bool $active): int
    {
        return $this->db->write(
            "UPDATE Kiosk SET Name = ?, Location = ?, Active = ? WHERE Kiosk_ID = ?",
            [$name, $location, (int) $active, $kiosk_id]
        );
    }
}
