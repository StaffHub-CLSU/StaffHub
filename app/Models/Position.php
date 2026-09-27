<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Position extends Model { public const UPDATED_AT=null; protected $primaryKey='position_id'; protected $fillable=['position_name','department_id']; }
