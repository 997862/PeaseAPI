<?php

namespace NewApi\Models;

use NewApi\Database\Model;

class MailTemplate extends Model
{
    protected static string $table = 'mail_templates';

    public static function findBySlug(string $slug): ?MailTemplate
    {
        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM mail_templates WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? new self($data) : null;
    }

    public static function getAllTemplates(): array
    {
        $db = \NewApi\Database\Connection::getInstance();
        $stmt = $db->query("SELECT * FROM mail_templates ORDER BY is_system DESC, id ASC");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
    }

    /**
     * 搜索分页（与父类 paginate 区分）
     */
    public static function searchPaginate(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $db = \NewApi\Database\Connection::getInstance();
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if (!empty($search)) {
            $where = "WHERE name LIKE ? OR slug LIKE ? OR description LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }

        // Count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM mail_templates {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Data
        $dataStmt = $db->prepare("SELECT * FROM mail_templates {$where} ORDER BY is_system DESC, id ASC LIMIT ? OFFSET ?");
        $dataStmt->execute([...$params, $perPage, $offset]);
        $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'items' => array_map(fn($r) => new self($r), $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * 渲染模板，替换变量
     */
    public function render(array $variables = []): array
    {
        $subject = $this->subject;
        $content = $this->content;

        $defaults = [
            'site_name' => Option::get('SystemName', 'PeaseAPI'),
            'site_url' => Option::get('FrontendURL', 'https://www.peaseapi.com'),
        ];
        $variables = array_merge($defaults, $variables);

        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    /**
     * 发送模板邮件
     */
    public function sendTo(string $toEmail, array $variables = []): array
    {
        $rendered = $this->render($variables);
        $mailer = new \NewApi\Services\SmtpMailer();
        return $mailer->send($toEmail, $rendered['subject'], $rendered['content']);
    }

    public function save(): bool
    {
        $this->updated_at = date('Y-m-d H:i:s');
        return parent::save();
    }
}
