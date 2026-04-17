<?php
/**
 * AuditLogModel — handles all Audit_Log operations
 */
class AuditLogModel extends Model
{
    /** Write an entry to the audit log */
    public function log(int $user_id, string $action, ?string $details = null): int
    {
        return $this->db->insert(
            "INSERT INTO Audit_Log (User_ID, Action, Details, Timestamp)
             VALUES (?, ?, ?, NOW())",
            [$user_id, $action, $details]
        );
    }

    /** Get all audit log entries (with optional filters) */
    public function getAll(?int $user_id = null, ?string $from_date = null, ?string $to_date = null): array
    {
        $sql = "SELECT a.*, u.Username, u.Full_name
                FROM Audit_Log a
                LEFT JOIN User u ON a.User_ID = u.User_ID
                WHERE 1=1";
        $params = [];

        if ($user_id !== null) {
            $sql .= " AND a.User_ID = ?";
            $params[] = $user_id;
        }
        if ($from_date !== null) {
            $sql .= " AND a.Timestamp >= ?";
            $params[] = $from_date . ' 00:00:00';
        }
        if ($to_date !== null) {
            $sql .= " AND a.Timestamp <= ?";
            $params[] = $to_date . ' 23:59:59';
        }

        $sql .= " ORDER BY a.Timestamp DESC";

        return $this->db->read($sql, $params);
    }
}
