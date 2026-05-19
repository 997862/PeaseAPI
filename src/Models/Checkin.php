<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class Checkin extends Model {
    protected static string $table = 'checkins';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'quota', 'created_at'];
    protected static array $casts = ['id'=>'int','user_id'=>'int','quota'=>'int','created_at'=>'int'];
    public static function hasCheckedInToday(int $userId): bool {
        $todayStart = strtotime(date('Y-m-d'));
        return self::query()->where('user_id', $userId)->whereRaw('created_at >= ?', [$todayStart])->exists();
    }
}
