<?php
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            putenv(trim($k).'='.trim($v, " '\""));
        }
    }
}
use NewApi\Models\MailTemplate;
$r = MailTemplate::searchPaginate(1, 20, '');
echo 'MailTemplate OK: ' . $r['total'] . ' templates' . PHP_EOL;
use NewApi\Models\Option;
$all = Option::getAll();
echo 'Option OK: ' . count($all) . ' options loaded' . PHP_EOL;
