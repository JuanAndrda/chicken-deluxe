<?php
/**
 * UserModel — handles all User table operations
 */
class UserModel extends Model
{
    /** Find a user by username (for login) */
    public function findByUsername(string $username): ?array
    {
        return $this->db->readOne(
            "SELECT u.*, r.Name AS Role_Name, k.Name AS Kiosk_Name
             FROM User u
             JOIN Role r ON u.Role_ID = r.Role_ID
             LEFT JOIN Kiosk k ON u.Outlet_ID = k.Kiosk_ID
             WHERE u.Username = ?",
            [$username]
        );
    }

    /** Find a user by ID */
    public function findById(int $user_id): ?array
    {
        return $this->db->readOne(
            "SELECT u.*, r.Name AS Role_Name, k.Name AS Kiosk_Name
             FROM User u
             JOIN Role r ON u.Role_ID = r.Role_ID
             LEFT JOIN Kiosk k ON u.Outlet_ID = k.Kiosk_ID
             WHERE u.User_ID = ?",
            [$user_id]
        );
    }

    /** Get all active users */
    public function getAllActive(): array
    {
        return $this->db->read(
            "SELECT u.*, r.Name AS Role_Name, k.Name AS Kiosk_Name
             FROM User u
             JOIN Role r ON u.Role_ID = r.Role_ID
             LEFT JOIN Kiosk k ON u.Outlet_ID = k.Kiosk_ID
             WHERE u.Active_status = 1
             ORDER BY u.User_ID"
        );
    }

    /** Create a new user */
    public function create(int $role_id, ?int $outlet_id, string $username, string $password, string $full_name): int
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        return $this->db->insert(
            "INSERT INTO User (Role_ID, Outlet_ID, Username, Password, Full_name)
             VALUES (?, ?, ?, ?, ?)",
            [$role_id, $outlet_id, $username, $hashed, $full_name]
        );
    }

    /** Update a user's active status */
    public function setActive(int $user_id, bool $active): int
    {
        return $this->db->write(
            "UPDATE User SET Active_status = ? WHERE User_ID = ?",
            [(int) $active, $user_id]
        );
    }
}
