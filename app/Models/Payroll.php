<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payroll';

    protected $primaryKey = 'payroll_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $fillable = ['employee_id', 'payroll_period_start', 'payroll_period_end', 'verified_hours', 'hourly_rate', 'gross_salary', 'deductions', 'bonuses', 'net_salary', 'processed_by', 'processed_date'];

    protected function casts(): array
    {
        return ['payroll_period_start' => 'date', 'payroll_period_end' => 'date', 'processed_date' => 'datetime', 'verified_hours' => 'decimal:2', 'hourly_rate' => 'decimal:2', 'gross_salary' => 'decimal:2', 'deductions' => 'decimal:2', 'bonuses' => 'decimal:2', 'net_salary' => 'decimal:2'];
    }
}
