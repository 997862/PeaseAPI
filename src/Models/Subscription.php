<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class Subscription extends Model {
    protected static string $table = 'subscriptions';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'user_id', 'product_id', 'status', 'start_at', 'end_at', 'cancel_at', 'trial_at', 'quota', 'auto_renew'];
    protected static array $casts = ['id'=>'int','user_id'=>'int','status'=>'int','start_at'=>'int','end_at'=>'int','cancel_at'=>'int','trial_at'=>'int','quota'=>'int','auto_renew'=>'bool'];
    public const STATUS_ACTIVE = 1;
    public const STATUS_CANCELLED = 2;
    public const STATUS_EXPIRED = 3;
}
