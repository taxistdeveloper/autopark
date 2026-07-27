<?php
namespace App\Models;

class AuditLog extends Model {
    public function add(string $action, string $entityType, ?string $entityId, ?string $entityName, ?string $details, ?int $entityVehicleId, ?int $userId, ?string $userName): void {
        $stmt = $this->db->prepare("INSERT INTO audit_log (action, entity_type, entity_id, entity_name, entity_vehicle_id, user_id, user_name, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$action, $entityType, $entityId, $entityName, $entityVehicleId, $userId, $userName, $details]);
    }

    public function all(int $limit = 5000): array {
        $limit = (int) $limit;
        if ($limit < 1) $limit = 5000;
        if ($limit > 50000) $limit = 50000;
        // MySQL LIMIT must be an integer literal; PDO binds it as string and causes syntax error
        $stmt = $this->db->query("SELECT * FROM audit_log ORDER BY id DESC LIMIT " . $limit);
        return array_map([$this, 'toApi'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function byVehicle(int $vehicleId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM audit_log 
            WHERE entity_vehicle_id = ? OR (entity_type = 'motohours' AND entity_id = ?) OR (entity_type = 'vehicle' AND entity_id = ?)
            ORDER BY id DESC
        ");
        $stmt->execute([$vehicleId, $vehicleId, $vehicleId]);
        return array_map([$this, 'toApi'], $stmt->fetchAll());
    }

    public function deleteEntry(int $id): bool {
        return $this->db->prepare("DELETE FROM audit_log WHERE id = ?")->execute([$id]);
    }

    public function clear(): void {
        $this->db->exec("TRUNCATE TABLE audit_log");
    }

    public function toApi(array $row): array {
        return [
            'id' => (int) $row['id'],
            'date' => $row['created_at'],
            'action' => $row['action'],
            'entityType' => $row['entity_type'],
            'entityId' => $row['entity_id'],
            'entityName' => $row['entity_name'],
            'entityVehicleId' => $row['entity_vehicle_id'] !== null ? (int) $row['entity_vehicle_id'] : null,
            'user' => $row['user_name'] ?? '',
            'details' => $row['details'] ?? '',
        ];
    }
}
