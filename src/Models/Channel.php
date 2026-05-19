<?php

namespace NewApi\Models;

use NewApi\Database\Model;
use NewApi\Database\Connection;
use PDO;

class Channel extends Model
{
    protected static string $table = 'channels';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [
        'id', 'type', 'key', 'openai_organization', 'test_model', 'status',
        'name', 'weight', 'created_time', 'test_time', 'response_time',
        'base_url', 'other', 'balance', 'balance_updated_time', 'models',
        'group', 'used_quota', 'model_mapping', 'status_code_mapping',
        'priority', 'auto_ban', 'other_info', 'tag', 'setting',
        'param_override', 'header_override', 'remark', 'channel_info',
        'settings',
    ];
    protected static array $casts = [
        'id' => 'int', 'type' => 'int', 'status' => 'int',
        'weight' => 'int', 'created_time' => 'int', 'test_time' => 'int',
        'response_time' => 'int', 'balance' => 'float',
        'balance_updated_time' => 'int', 'used_quota' => 'int',
        'auto_ban' => 'int', 'priority' => 'int',
        'channel_info' => 'json',
    ];

    // Channel types
    public const TYPE_OPENAI = 1;
    public const TYPE_CLAUDE = 14;
    public const TYPE_GEMINI = 15;
    public const TYPE_BAIDU = 4;
    public const TYPE_ZHIPU = 3;
    public const TYPE_ALI = 11;
    public const TYPE_AWS_CLAUDE = 16;
    public const TYPE_COHERE = 23;
    public const TYPE_COZE = 24;
    public const TYPE_DIFY = 25;
    public const TYPE_GROQ = 26;
    public const TYPE_JINA = 27;
    public const TYPE_MINIMAX = 28;
    public const TYPE_MISTRAL = 29;
    public const TYPE_MIDJOURNEY = 30;
    public const TYPE_MOONSHOT = 31;
    public const TYPE_OLLAMA = 32;
    public const TYPE_PERPLEXITY = 33;
    public const TYPE_REPLICATE = 34;
    public const TYPE_SILICONFLOW = 35;
    public const TYPE_TOGETHERAI = 36;
    public const TYPE_XUNFEI = 37;
    public const TYPE_AZURE = 22;
    public const TYPE_DEEPSEEK = 41;

    // Status
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 2;
    public const STATUS_MANUALLY_DISABLED = 3;
    public const STATUS_AUTO_DISABLED = 4;

    public static function getEnabledChannels(): array
    {
        return static::findAll(['status' => self::STATUS_ENABLED]);
    }

    public static function getChannelById(int $id, bool $throw = false): ?static
    {
        return static::find($id);
    }

    public static function getEnabledChannelsByGroup(string $group): array
    {
        return static::query()
            ->where('status', self::STATUS_ENABLED)
            ->whereRaw("(\"group\" LIKE ? OR \"group\" = ?)", ["%$group%", $group])
            ->orderBy('priority', 'DESC')
            ->get();
    }

    public static function getChannelsForModel(string $model, string $group = ''): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT * FROM channels WHERE status = ? AND enabled = TRUE";
        $bindings = [self::STATUS_ENABLED];

        if ($model) {
            $sql .= " AND (models LIKE ? OR models = '')";
            $bindings[] = "%$model%";
        }

        if ($group) {
            $sql .= " AND (\"group\" LIKE ? OR \"group\" = '')";
            $bindings[] = "%$group%";
        }

        $sql .= " ORDER BY priority DESC, weight DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($d) => (new static($d))->fill([]), $results);
    }

    public function getKeys(): array
    {
        if (empty($this->key)) {
            return [];
        }
        return explode("\n", trim($this->key));
    }

    public function addUsedQuota(int $quota): void
    {
        $db = Connection::getInstance();
        $sql = "UPDATE channels SET used_quota = used_quota + ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$quota, $this->id]);
        $this->used_quota += $quota;
    }

    public function setAutoDisabled(): void
    {
        if ($this->auto_ban === 1) {
            $this->status = self::STATUS_AUTO_DISABLED;
            $this->save();
        }
    }

    public function getBaseUrl(): string
    {
        return $this->base_url ?: '';
    }

    public function getModels(): array
    {
        if (empty($this->models)) {
            return [];
        }
        return explode(",", $this->models);
    }

    public function getModelMapping(): ?array
    {
        if (empty($this->model_mapping)) {
            return null;
        }
        return json_decode($this->model_mapping, true);
    }

    public function getStatusCodeMapping(): ?array
    {
        if (empty($this->status_code_mapping)) {
            return null;
        }
        return json_decode($this->status_code_mapping, true);
    }

    public function getChannelInfo(): array
    {
        return $this->channel_info ? json_decode($this->channel_info, true) : [];
    }

    public function getSettings(): array
    {
        return $this->settings ? json_decode($this->settings, true) : [];
    }

    public static function searchChannels(string $keyword, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $db = Connection::getInstance();
        $sql = "SELECT * FROM channels WHERE name LIKE ? OR key LIKE ? OR tag LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute(["%$keyword%", "%$keyword%", "%$keyword%", $perPage, $offset]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $countSql = "SELECT COUNT(*) FROM channels WHERE name LIKE ? OR key LIKE ? OR tag LIKE ?";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute(["%$keyword%", "%$keyword%", "%$keyword%"]);
        $total = (int) $countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public static function countByStatus(): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT status, COUNT(*) as count FROM channels GROUP BY status";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        return $counts;
    }

    public static function batchUpdateStatus(array $ids, int $status): int
    {
        $db = Connection::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE channels SET status = ? WHERE id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $bindings = array_merge([$status], $ids);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    public static function batchDelete(array $ids): int
    {
        $db = Connection::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM channels WHERE id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->rowCount();
    }
}
