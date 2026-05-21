<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;
use PDO;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [
        'id', 'username', 'password', 'display_name', 'role', 'status',
        'email', 'phone', 'quota', 'used_quota', 'request_count', 'group',
        'aff_code', 'aff_count', 'aff_quota', 'aff_history_quota',
        'inviter_id', 'access_token', 'setting', 'remark',
        'stripe_customer', 'created_at', 'last_login_at', 'last_login_ip', 'last_login_port',
        'registration_ip', 'github_id',
        'discord_id', 'oidc_id', 'wechat_id', 'telegram_id', 'linux_do_id',
        'deleted_at', 'original_password', 'verification_code',
    ];
    protected static array $casts = [
        'id' => 'int', 'role' => 'int', 'status' => 'int',
        'quota' => 'int', 'used_quota' => 'int', 'request_count' => 'int',
        'aff_count' => 'int', 'aff_quota' => 'int', 'aff_history_quota' => 'int',
        'inviter_id' => 'int', 'created_at' => 'int', 'last_login_at' => 'int',
        'last_login_port' => 'int',
        'deleted_at' => 'timestamp',
    ];

    // Roles
    public const ROLE_ROOT = 100;
    public const ROLE_ADMIN = 10;
    public const ROLE_USER = 1;

    // Status
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    public static function create(array $attributes): static
    {
        if (!isset($attributes['created_at'])) {
            $attributes['created_at'] = time();
        }
        return parent::create($attributes);
    }

    public static function createRootUser(): static
    {
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        return static::create([
            'username' => 'root',
            'password' => $hashedPassword,
            'display_name' => 'Root User',
            'role' => self::ROLE_ROOT,
            'status' => self::STATUS_ENABLED,
            'quota' => 0,
        ]);
    }

    public static function rootExists(): bool
    {
        return static::count() > 0;
    }

    public static function verifyUser(string $username, string $password): ?static
    {
        $user = static::firstWhere('username', $username);
        if (!$user) {
            return null;
        }
        if (password_verify($password, $user->password)) {
            return $user;
        }
        return null;
    }

    public static function getByEmail(string $email): ?static
    {
        return static::firstWhere('email', $email);
    }

    public static function getByAccessToken(string $token): ?static
    {
        return static::firstWhere('access_token', $token);
    }

    public static function getByAffCode(string $affCode): ?static
    {
        return static::firstWhere('aff_code', $affCode);
    }

    public static function usernameExists(string $username): bool
    {
        return static::query()->where('username', $username)->exists();
    }

    public static function emailExists(string $email): bool
    {
        return static::query()->where('email', $email)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role >= self::ROLE_ADMIN;
    }

    public function isRoot(): bool
    {
        return $this->role === self::ROLE_ROOT;
    }

    public function setAccessToken(): string
    {
        $token = bin2hex(random_bytes(16));
        $this->access_token = $token;
        $this->save();
        return $token;
    }

    public function generateAffCode(): string
    {
        $affCode = substr(md5($this->username . time() . random_int(0, 999999)), 0, 8);
        $this->aff_code = $affCode;
        $this->save();
        return $affCode;
    }

    public function addQuota(int $quota): void
    {
        $this->quota += $quota;
        $this->save();
    }

    public function consumeQuota(int $quota): bool
    {
        if ($this->quota < $quota) {
            return false;
        }
        $this->quota -= $quota;
        $this->used_quota += $quota;
        $this->request_count++;
        $this->save();
        return true;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->setting ? json_decode($this->setting, true) : [];
        return $settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->setting ? json_decode($this->setting, true) : [];
        $settings[$key] = $value;
        $this->setting = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    public static function searchUsers(string $keyword, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM users WHERE username LIKE ? OR display_name LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute(["%$keyword%", "%$keyword%", "%$keyword%", $perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $countSql = "SELECT COUNT(*) FROM users WHERE username LIKE ? OR display_name LIKE ? OR email LIKE ?";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute(["%$keyword%", "%$keyword%", "%$keyword%"]);
        $total = (int) $countStmt->fetchColumn();

        return [
            'items' => array_map(fn($d) => (new static($d))->fill([])),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }
}
