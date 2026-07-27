<?php
namespace App\Models;

class Rent extends Model {
    public function all(string $search = ''): array {
        $sql = "SELECT r.*, v.name AS vehicle_name, v.grnz AS vehicle_grnz FROM rents r JOIN vehicles v ON v.id = r.vehicle_id ORDER BY r.start_date DESC";
        $params = [];
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql = "SELECT r.*, v.name AS vehicle_name, v.grnz AS vehicle_grnz FROM rents r JOIN vehicles v ON v.id = r.vehicle_id WHERE v.name LIKE ? OR v.grnz LIKE ? OR r.tenant LIKE ? OR r.status LIKE ? OR r.start_date LIKE ? OR r.end_date LIKE ? ORDER BY r.start_date DESC";
            $params = array_fill(0, 6, $q);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'toApi'], $stmt->fetchAll());
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare("SELECT r.*, v.name AS vehicle_name, v.grnz AS vehicle_grnz FROM rents r JOIN vehicles v ON v.id = r.vehicle_id WHERE r.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->toApi($row) : null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO rents (vehicle_id, tenant, start_date, end_date, total, income, profit, diesel_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int) $data['vehicleId'],
            $data['tenant'] ?? '',
            $data['startDate'] ?? null,
            $data['endDate'] ?? null,
            $this->floatOrNull($data['total'] ?? null),
            $this->floatOrNull($data['income'] ?? null),
            $this->floatOrNull($data['profit'] ?? null),
            $this->floatOrNull($data['dieselCost'] ?? null),
            $data['status'] ?? 'Активна',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM rents WHERE id = ?")->execute([$id]);
    }

    public function toApi(array $row): array {
        return [
            'id' => (string) $row['id'],
            'vehicleId' => (string) $row['vehicle_id'],
            'vehicleName' => $row['vehicle_name'] ?? $row['vehicleName'] ?? '',
            'vehicleGrnz' => $row['vehicle_grnz'] ?? $row['vehicleGrnz'] ?? '',
            'tenant' => $row['tenant'] ?? '',
            'startDate' => $row['start_date'] ?? null,
            'endDate' => $row['end_date'] ?? null,
            'total' => $row['total'] !== null ? (float) $row['total'] : null,
            'income' => $row['income'] !== null ? (float) $row['income'] : null,
            'profit' => $row['profit'] !== null ? (float) $row['profit'] : null,
            'dieselCost' => $row['diesel_cost'] !== null ? (float) $row['diesel_cost'] : null,
            'status' => $row['status'] ?? 'Активна',
        ];
    }

    private function floatOrNull($v) {
        if ($v === null || $v === '') return null;
        $f = (float) $v;
        return is_finite($f) ? $f : null;
    }
}
