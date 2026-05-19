<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class TwoFASecret extends Model {
    protected static string $table = 'twofa_secrets';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'secret', 'enabled', 'created_at'];
    protected static array $casts = ['id'=>'int','user_id'=>'int','enabled'=>'bool','created_at'=>'int'];
    public static function getByUserId(int $userId): ?static {
        return static::firstWhere('user_id', $userId);
    }
}
