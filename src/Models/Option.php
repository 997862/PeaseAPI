<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;

class Option extends Model
{
    protected static string $table = 'options';
    protected static string $primaryKey = 'key';
    protected static array $fillable = ['key', 'value'];
    protected static array $hiddenSecrets = ['secret', 'token', 'password', 'key', 'crypto'];

    private static ?array $cache = null;

    public static function getAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $options = static::findAll();
        self::$cache = [];
        foreach ($options as $option) {
            self::$cache[$option->key] = $option->value;
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::getAll();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $existing = static::findWhere(['key' => $key]);
        if ($existing) {
            $existing->value = $value;
            $existing->save();
        } else {
            static::create(['key' => $key, 'value' => $value]);
        }
        self::$cache = null;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key);
        if ($val === null) return $default;
        return in_array(strtolower($val), ['true', '1', 'yes', 'on']);
    }

    public static function getFilteredOptions(): array
    {
        $all = self::getAll();
        $filtered = [];
        foreach ($all as $key => $value) {
            $isSecret = false;
            foreach (self::$hiddenSecrets as $secretWord) {
                if (stripos($key, $secretWord) !== false) {
                    $isSecret = true;
                    break;
                }
            }
            if (!$isSecret) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }

    public static function refreshCache(): void
    {
        self::$cache = null;
    }

    public static function updateOption(string $key, string $value): void
    {
        self::set($key, $value);
    }
}
