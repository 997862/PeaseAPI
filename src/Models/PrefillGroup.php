<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class PrefillGroup extends Model {
    protected static string $table = 'prefill_groups';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'name', 'models', 'created_at'];
    protected static array $casts = ['id'=>'int','models'=>'json','created_at'=>'int'];
}
