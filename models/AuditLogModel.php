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

    /** Get audit log entries with optional filters and pagination */
    public function getAll(
        ?int    $user_id    = null,
        ?string $from_date  = null,
        ?string $to_date    = null,
        ?string $action     = null,
        int     $limit      = 20,
        int     $offset     = 0
    ): array {
        $sql = "SELECT a.*, u.Username, u.Full_name
                FROM Audit_Log a
                LEFT JOIN User u ON a.User_ID = u.User_ID
                WHERE 1=1";
        $params = [];

        if ($user_id !== null) {
            $sql .= " AND a.User_ID = ?";
            $params[] = $user_id;
        }
        if ($from_date !== null && $from_date !== '') {
            $sql .= " AND a.Timestamp >= ?";
            $params[] = $from_date . ' 00:00:00';
        }
        if ($to_date !== null && $to_date !== '') {
            $sql .= " AND a.Timestamp <= ?";
            $params[] = $to_date . ' 23:59:59';
        }
        if ($action !== null && $action !== '') {
            $sql .= " AND a.Action = ?";
            $params[] = $action;
        }

        // Order by timestamp, then Log_ID as a tiebreaker.
        // Trigger-level INSERT and application-level CREATE rows often share
        // the same second; Log_ID (auto-increment) guarantees strict
        // insertion order within the same timestamp.
        $sql .= " ORDER BY a.Timestamp DESC, a.Log_ID DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->read($sql, $params);
    }

    /** Count total matching rows (for pagination calculation) */
    public function countAll(
        ?int    $user_id   = null,
        ?string $from_date = null,
        ?string $to_date   = null,
        ?string $action    = null
    ): int {
        $sql = "SELECT COUNT(*) AS total FROM Audit_Log a WHERE 1=1";
        $params = [];

        if ($user_id !== null) {
            $sql .= " AND a.User_ID = ?";
            $params[] = $user_id;
        }
        if ($from_date !== null && $from_date !== '') {
            $sql .= " AND a.Timestamp >= ?";
            $params[] = $from_date . ' 00:00:00';
        }
        if ($to_date !== null && $to_date !== '') {
            $sql .= " AND a.Timestamp <= ?";
            $params[] = $to_date . ' 23:59:59';
        }
        if ($action !== null && $action !== '') {
            $sql .= " AND a.Action = ?";
            $params[] = $action;
        }

        $row = $this->db->readOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }
}
