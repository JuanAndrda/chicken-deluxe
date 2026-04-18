<?php
/**
 * TimeInModel — handles Time_in table operations for staff attendance
 */
class TimeInModel extends Model
{
    /** Record a time-in for a user */
    public function recordTimeIn(int $user_id, int $kiosk_id): int
    {
        return $this->db->insert(
            "INSERT INTO Time_in (User_ID, Kiosk_ID) VALUES (?, ?)",
            [$user_id, $kiosk_id]
        );
    }

    /** Check if user already timed in today */
    public function hasTimedInToday(int $user_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt FROM Time_in
             WHERE User_ID = ? AND DATE(Timestamp) = CURDATE()",
            [$user_id]
        );
        return ($row['cnt'] ?? 0) > 0;
    }

    /** Get time-in records for a date range, optional kiosk filter */
    public function getByDateRange(string $from_date, string $to_date, ?int $kiosk_id = null): array
    {
        $sql = "SELECT t.*, u.Full_name, u.Username, k.Name AS Kiosk_Name
                FROM Time_in t
                JOIN User u ON t.User_ID = u.User_ID
                JOIN Kiosk k ON t.Kiosk_ID = k.Kiosk_ID
                WHERE DATE(t.Timestamp) BETWEEN ? AND ?";
        $params = [$from_date, $to_date];

        if ($kiosk_id !== null) {
            $sql .= " AND t.Kiosk_ID = ?";
            $params[] = $kiosk_id;
        }

        $sql .= " ORDER BY t.Timestamp DESC";

        return $this->db->read($sql, $params);
    }
}
