<?php
namespace App\Models;

class Vehicle extends Model {
    public function all(string $search = ''): array {
        $sql = "SELECT * FROM vehicles ORDER BY name";
        $params = [];
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql = "SELECT * FROM vehicles WHERE name LIKE ? OR owner LIKE ? OR grnz LIKE ? OR location LIKE ? OR application LIKE ? OR consumption_rate LIKE ? OR diesel_fuel LIKE ? OR year LIKE ? ORDER BY name";
            $params = array_fill(0, 8, $q);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'toApi'], $stmt->fetchAll());
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->toApi($row) : null;
    }

    public function create(array $data): int {
        $cols = ['name','owner','grnz','consumption_rate','diesel_fuel','year','motor_hours_base','motor_hours_next1','motor_hours_next2','motor_hours_next3','motor_hours','application','location','insurance_date','insurance_deadline','tech_date','tech_deadline','tax_date','tax_deadline'];
        $fields = $this->toDb($data);
        $keys = array_intersect_key($fields, array_flip($cols));
        $names = implode(',', array_keys($keys));
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $this->db->prepare("INSERT INTO vehicles ($names) VALUES ($placeholders)")->execute(array_values($keys));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $cols = ['name','owner','grnz','consumption_rate','diesel_fuel','year','motor_hours_base','motor_hours_next1','motor_hours_next2','motor_hours_next3','motor_hours','application','location','insurance_date','insurance_deadline','tech_date','tech_deadline','tax_date','tax_deadline'];
        $fields = $this->toDb($data);
        $keys = array_intersect_key($fields, array_flip($cols));
        $set = implode(', ', array_map(fn($k) => "`$k`=?", array_keys($keys)));
        $keys['id'] = $id;
        $stmt = $this->db->prepare("UPDATE vehicles SET $set WHERE id = ?");
        return $stmt->execute(array_values($keys));
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$id]);
    }

    public function updateApplication(int $id, ?string $application): bool {
        return $this->db->prepare("UPDATE vehicles SET application = ? WHERE id = ?")->execute([$application, $id]);
    }

    public function toApi(array $row): array {
        return [
            'id' => (string) $row['id'],
            'name' => $row['name'] ?? '',
            'owner' => $row['owner'] ?? '',
            'grnz' => $row['grnz'] ?? null,
            'consumptionRate' => $row['consumption_rate'] ?? null,
            'dieselFuel' => $row['diesel_fuel'] ?? null,
            'year' => $row['year'] !== null ? (string) $row['year'] : null,
            'motorHoursBase' => $row['motor_hours_base'] !== null ? (float) $row['motor_hours_base'] : null,
            'motorHoursNext1' => $row['motor_hours_next1'] !== null ? (float) $row['motor_hours_next1'] : null,
            'motorHoursNext2' => $row['motor_hours_next2'] !== null ? (float) $row['motor_hours_next2'] : null,
            'motorHoursNext3' => $row['motor_hours_next3'] !== null ? (float) $row['motor_hours_next3'] : null,
            'motorHours' => $row['motor_hours'] !== null ? (float) $row['motor_hours'] : null,
            'application' => $row['application'] ?? null,
            'location' => $row['location'] ?? null,
            'insuranceDate' => $row['insurance_date'] ?? null,
            'insuranceDeadline' => $row['insurance_deadline'] ?? null,
            'techDate' => $row['tech_date'] ?? null,
            'techDeadline' => $row['tech_deadline'] ?? null,
            'taxDate' => $row['tax_date'] ?? null,
            'taxDeadline' => $row['tax_deadline'] ?? null,
        ];
    }

    private function toDb(array $data): array {
        $map = [
            'name' => $data['name'] ?? null,
            'owner' => $data['owner'] ?? null,
            'grnz' => $data['grnz'] ?? null,
            'consumption_rate' => $data['consumptionRate'] ?? null,
            'diesel_fuel' => $data['dieselFuel'] ?? null,
            'year' => isset($data['year']) && $data['year'] !== '' ? (int) $data['year'] : null,
            'motor_hours_base' => $this->floatOrNull($data['motorHoursBase'] ?? null),
            'motor_hours_next1' => $this->floatOrNull($data['motorHoursNext1'] ?? null),
            'motor_hours_next2' => $this->floatOrNull($data['motorHoursNext2'] ?? null),
            'motor_hours_next3' => $this->floatOrNull($data['motorHoursNext3'] ?? null),
            'motor_hours' => $this->floatOrNull($data['motorHours'] ?? null),
            'application' => $data['application'] ?? null,
            'location' => $data['location'] ?? null,
            'insurance_date' => $data['insuranceDate'] ?? null,
            'insurance_deadline' => $data['insuranceDeadline'] ?? null,
            'tech_date' => $data['techDate'] ?? null,
            'tech_deadline' => $data['techDeadline'] ?? null,
            'tax_date' => $data['taxDate'] ?? null,
            'tax_deadline' => $data['taxDeadline'] ?? null,
        ];
        return array_filter($map, fn($v) => $v !== null);
    }

    private function floatOrNull($v) {
        if ($v === null || $v === '') return null;
        $f = (float) $v;
        return is_finite($f) ? $f : null;
    }
}
