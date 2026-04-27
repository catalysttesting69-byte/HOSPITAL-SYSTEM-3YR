<?php
// ============================================================
// includes/audit.php — Blockchain-like Audit Logging
// ============================================================

require_once __DIR__ . '/../config/db.php';

/**
 * Write an immutable audit log entry.
 *
 * Hash formula:
 *   current_hash = SHA256(action + record_id + timestamp + previous_hash)
 *
 * @param string   $action      e.g. "UPLOAD", "TRANSFER", "VIEW", "LOGIN"
 * @param int      $performedBy User ID
 * @param int|null $recordId    Patient record ID (null for non-record actions)
 */
function writeAuditLog(string $action, int $performedBy, ?int $recordId = null): void {
    $pdo       = getDB();
    $timestamp = date('Y-m-d H:i:s');

    // Fetch the previous hash (last entry)
    $stmt = $pdo->query("SELECT current_hash FROM audit_logs ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetch();
    $previousHash = $last ? $last['current_hash'] : '0000000000000000000000000000000000000000000000000000000000000000';

    // Compute current hash
    $raw         = $action . ($recordId ?? 'null') . $timestamp . $previousHash;
    $currentHash = hash('sha256', $raw);

    // Insert log entry
    $ins = $pdo->prepare(
        "INSERT INTO audit_logs (action, performed_by, record_id, timestamp, previous_hash, current_hash)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $ins->execute([$action, $performedBy, $recordId, $timestamp, $previousHash, $currentHash]);
}

/**
 * Verify the integrity of the entire audit chain.
 * Returns ['valid' => true] or ['valid' => false, 'broken_at_id' => int]
 */
function verifyAuditChain(): array {
    $pdo  = getDB();
    $rows = $pdo->query("SELECT * FROM audit_logs ORDER BY id ASC")->fetchAll();

    $prevHash = '0000000000000000000000000000000000000000000000000000000000000000';

    foreach ($rows as $row) {
        $raw      = $row['action'] . ($row['record_id'] ?? 'null') . $row['timestamp'] . $prevHash;
        $expected = hash('sha256', $raw);

        if ($expected !== $row['current_hash'] || $prevHash !== $row['previous_hash']) {
            return ['valid' => false, 'broken_at_id' => $row['id']];
        }
        $prevHash = $row['current_hash'];
    }

    return ['valid' => true];
}
