<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class Passkey extends Model {
    protected static string $table = 'passkeys';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'name', 'credential_id', 'public_key', 'counter', 'created_at'];
    protected static array $casts = ['id'=>'int','user_id'=>'int','counter'=>'int','created_at'=>'int'];
}
