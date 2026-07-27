<?php
namespace App\Models;

class Spare extends Model {
    public function all(string $search = ''): array {
        $sql = "SELECT s.*, v.name AS vehicle_name, v.owner AS vehicle_owner FROM spares s JOIN vehicles v ON v.id = s.vehicle_id ORDER BY s.date DESC, s.id DESC";
        $params = [];
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql = "SELECT s.*, v.name AS vehicle_name, v.owner AS vehicle_owner FROM spares s JOIN vehicles v ON v.id = s.vehicle_id WHERE v.name LIKE ? OR v.owner LIKE ? OR s.spare_name LIKE ? OR s.date LIKE ? OR s.amount LIKE ? OR s.quantity LIKE ? ORDER BY s.date DESC, s.id DESC";
            $params = array_fill(0, 6, $q);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'toApi'], $stmt->fetchAll());
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare("SELECT s.*, v.name AS vehicle_name, v.owner AS vehicle_owner FROM spares s JOIN vehicles v ON v.id = s.vehicle_id WHERE s.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->toApi($row) : null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO spares (vehicle_id, spare_name, quantity, amount, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            (int) $data['vehicleId'],
            $data['spareName'] ?? '',
            $this->floatOrNull($data['quantity'] ?? null),
            $this->floatOrNull($data['amount'] ?? null),
            $data['date'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE spares SET vehicle_id = ?, spare_name = ?, quantity = ?, amount = ?, date = ? WHERE id = ?");
        return $stmt->execute([
            (int) $data['vehicleId'],
            $data['spareName'] ?? '',
            $this->floatOrNull($data['quantity'] ?? null),
            $this->floatOrNull($data['amount'] ?? null),
            $data['date'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM spares WHERE id = ?")->execute([$id]);
    }

    public function toApi(array $row): array {
        return [
            'id' => (string) $row['id'],
            'vehicleId' => (string) $row['vehicle_id'],
            'vehicleName' => $row['vehicle_name'] ?? $row['vehicleName'] ?? '',
            'vehicleOwner' => $row['vehicle_owner'] ?? $row['vehicleOwner'] ?? '',
            'spareName' => $row['spare_name'] ?? $row['spareName'] ?? '',
            'quantity' => $row['quantity'] !== null ? (float) $row['quantity'] : null,
            'amount' => $row['amount'] !== null ? (float) $row['amount'] : null,
            'date' => $row['date'] ?? null,
        ];
    }

    private function floatOrNull($v) {
        if ($v === null || $v === '') return null;
        $f = (float) $v;
        return is_finite($f) ? $f : null;
    }
}
