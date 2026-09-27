<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Department extends Model { public const UPDATED_AT=null; protected $primaryKey='department_id'; protected $fillable=['department_name','description']; }
