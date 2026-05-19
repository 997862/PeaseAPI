<?php
namespace NewApi\Models;
use NewApi\Database\Model;
class Pricing extends Model {
    protected static string $table = 'pricing';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['id', 'model_name', 'unit_price', 'currency', 'type', 'created_at', 'updated_at'];
    protected static array $casts = ['id'=>'int','unit_price'=>'float','created_at'=>'int','updated_at'=>'int'];
}
