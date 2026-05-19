<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;

class Ability extends Model
{
    protected static string $table = 'abilities';
    protected static array $fillable = ['group', 'model', 'channel_id', 'enabled', 'priority', 'weight', 'tag'];
    protected static array $casts = [
        'channel_id' => 'int', 'enabled' => 'bool',
        'priority' => 'int', 'weight' => 'int',
    ];

    public static function getAllEnabled(): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT a.*, c.type as channel_type FROM abilities a LEFT JOIN channels c ON a.channel_id = c.id WHERE a.enabled = TRUE";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getModelsByGroup(string $group): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT DISTINCT model FROM abilities WHERE \"group\" = ? AND enabled = TRUE";
        $stmt = $db->prepare($sql);
        $stmt->execute([$group]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function getAllModels(): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT DISTINCT model FROM abilities WHERE enabled = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function getChannelsForModel(string $model, string $group = ''): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT a.*, c.type as channel_type, c.key, c.base_url, c.status, c.weight as channel_weight, c.priority as channel_priority, c.model_mapping, c.status_code_mapping, c.setting, c.param_override, c.header_override, c.other, c.openai_organization FROM abilities a LEFT JOIN channels c ON a.channel_id = c.id WHERE a.model = ? AND a.enabled = TRUE AND c.status = 1";
        $bindings = [$model];
        if ($group) {
            $sql .= " AND a.\"group\" = ?";
            $bindings[] = $group;
        }
        $sql .= " ORDER BY a.priority DESC, a.weight DESC, c.priority DESC, c.weight DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function rebuildAbilities(): void
    {
        $db = Connection::getInstance();
        $db->exec("TRUNCATE TABLE abilities");
        $channels = $db->query("SELECT * FROM channels WHERE status = 1")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($channels as $channel) {
            $models = array_filter(array_map('trim', explode(',', $channel['models'])));
            $groups = array_filter(array_map('trim', explode(',', $channel['group'] ?: 'default')));
            if (empty($groups)) $groups = ['default'];
            foreach ($models as $model) {
                foreach ($groups as $group) {
                    if (empty($group)) $group = 'default';
                    $stmt = $db->prepare("INSERT INTO abilities (\"group\", model, channel_id, enabled, priority, weight) VALUES (?, ?, ?, TRUE, ?, ?) ON CONFLICT DO NOTHING");
                    $stmt->execute([$group, $model, (int)$channel['id'], (int)($channel['priority'] ?? 0), (int)($channel['weight'] ?? 0)]);
                }
            }
        }
    }

    public static function deleteByChannelId(int $channelId): int
    {
        $db = Connection::getInstance();
        $sql = "DELETE FROM abilities WHERE channel_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$channelId]);
        return $stmt->rowCount();
    }
}
