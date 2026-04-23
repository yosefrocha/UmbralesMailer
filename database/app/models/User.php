<?php

declare(strict_types=1);

final class User extends Model
{
    public function all(): array
    {
        return $this->fetchAll('SELECT id, name, email, role, is_active, last_login_at, created_at FROM users ORDER BY created_at DESC');
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :password_hash, :role, :is_active)';
        $this->execute($sql, [
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateUser(int $id, array $data): void
    {
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        $sql = 'UPDATE users SET name = :name, email = :email, role = :role, is_active = :is_active';
        if (!empty($data['password'])) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id = :id';
        $this->execute($sql, $params);
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->execute('UPDATE users SET password_hash = :hash WHERE id = :id', [
            'id' => $id,
            'hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public function touchLastLogin(int $id): void
    {
        $this->execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $this->execute('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id', ['id' => $id]);
    }

    public function createTempPassword(int $userId, string $plainText, int $minutes = 30): void
    {
        $this->execute('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL', ['user_id' => $userId]);
        $minutes = max(1, $minutes);
        $sql = 'INSERT INTO password_resets (user_id, temp_password_hash, expires_at) VALUES (:user_id, :hash, DATE_ADD(NOW(), INTERVAL ' . $minutes . ' MINUTE))';
        $this->execute($sql, [
            'user_id' => $userId,
            'hash' => password_hash($plainText, PASSWORD_BCRYPT),
        ]);
    }

    public function latestValidTempPassword(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM password_resets WHERE user_id = :user_id AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function consumeTempPassword(int $resetId): void
    {
        $this->execute('UPDATE password_resets SET used_at = NOW() WHERE id = :id', ['id' => $resetId]);
    }
}
