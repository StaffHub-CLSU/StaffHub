<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Attendance extends Model { protected $table='attendance'; protected $primaryKey='attendance_id'; protected $fillable=['employee_id','attendance_date','time_in','time_out','total_hours','status']; protected function casts(): array { return ['attendance_date'=>'date','time_in'=>'datetime','time_out'=>'datetime','total_hours'=>'decimal:2']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class,'employee_id'); } }
