<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class OAuthBinding extends Model {
    protected static string $table = 'oauth_bindings';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'provider', 'provider_id', 'created_at'];
    protected static array $casts = ['id'=>'int','user_id'=>'int','created_at'=>'int'];
    public static function findByProvider(string $provider, string $providerId): ?static {
        return static::findWhere(['provider' => $provider, 'provider_id' => $providerId]);
    }
}
