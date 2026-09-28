<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees')) {
            return;
        }
        Schema::create('departments', function (Blueprint $table): void {
            $table->id('department_id');
            $table->string('department_name')->unique();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('positions', function (Blueprint $table): void {
            $table->id('position_id');
            $table->string('position_name');
            $table->foreignId('department_id')->nullable()->constrained('departments', 'department_id')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['position_name', 'department_id']);
        });
        Schema::create('employees', function (Blueprint $table): void {
            $table->id('employee_id');
            $table->string('employee_code')->unique();
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreignId('department_id')->nullable()->constrained('departments', 'department_id')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions', 'position_id')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('gender', 20);
            $table->date('birthdate');
            $table->string('email')->unique();
            $table->string('contact_number');
            $table->string('address')->nullable();
            $table->string('employment_status')->default('Full-Time');
            $table->decimal('basic_hourly_rate', 10, 2)->default(0);
            $table->date('date_hired');
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
        Schema::create('attendance', function (Blueprint $table): void {
            $table->id('attendance_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->string('status', 20)->default('Incomplete');
            $table->timestamps();
            $table->unique(['employee_id', 'attendance_date']);
        });
        Schema::create('payroll', function (Blueprint $table): void {
            $table->id('payroll_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->date('payroll_period_start');
            $table->date('payroll_period_end');
            $table->decimal('verified_hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('bonuses', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_date')->useCurrent();
            $table->foreign('processed_by')->references('user_id')->on('users')->nullOnDelete();
            $table->unique(['employee_id', 'payroll_period_start', 'payroll_period_end']);
        });
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id('log_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('activity');
            $table->timestamp('timestamp')->useCurrent();
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('payroll');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
