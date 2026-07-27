<?php
namespace App\Models;

class User extends Model {
    public function findByLogin(string $login): ?array {
        $stmt = $this->db->prepare("SELECT id, login, password_hash, name, is_admin FROM users WHERE login = ? LIMIT 1");
        $stmt->execute([$login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function verifyPassword(string $login, string $password): ?array {
        $user = $this->findByLogin($login);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        unset($user['password_hash']);
        return $user;
    }

    /** Список всех пользователей (без пароля) для админки */
    public function findAll(): array {
        $stmt = $this->db->query("SELECT id, login, name, is_admin, created_at FROM users ORDER BY is_admin DESC, login");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    /** Создание пользователя (менеджера или админа). Возвращает данные без password_hash. */
    public function create(string $login, string $password, string $name, bool $is_admin = false): array {
        $login = trim($login);
        if ($login === '') {
            throw new \InvalidArgumentException('Логин не может быть пустым');
        }
        if (mb_strlen($password) < 1) {
            throw new \InvalidArgumentException('Укажите пароль');
        }
        $stmt = $this->db->prepare("INSERT INTO users (login, password_hash, name, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $login,
            password_hash($password, PASSWORD_DEFAULT),
            trim($name) ?: $login,
            $is_admin ? 1 : 0
        ]);
        $id = (int) $this->db->lastInsertId();
        $user = $this->findByLogin($login);
        if ($user) {
            unset($user['password_hash']);
            return $user;
        }
        return ['id' => $id, 'login' => $login, 'name' => trim($name) ?: $login, 'is_admin' => $is_admin];
    }
}
