<?php
namespace App\Models;

class MotorHoursHistory extends Model {
    public function add(int $vehicleId, float $amount, float $totalAfter): void {
        $this->db->prepare("INSERT INTO vehicle_motor_hours_history (vehicle_id, amount, total_after) VALUES (?, ?, ?)")
            ->execute([$vehicleId, $amount, $totalAfter]);
    }

    public function byVehicle(int $vehicleId): array {
        $stmt = $this->db->prepare("SELECT * FROM vehicle_motor_hours_history WHERE vehicle_id = ? ORDER BY id DESC");
        $stmt->execute([$vehicleId]);
        return array_map([$this, 'toApi'], $stmt->fetchAll());
    }

    public function toApi(array $row): array {
        return [
            'date' => $row['created_at'],
            'amount' => (float) $row['amount'],
            'totalAfter' => (float) $row['total_after'],
        ];
    }
}
