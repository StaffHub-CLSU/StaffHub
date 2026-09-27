<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable { use HasFactory, Notifiable; protected $primaryKey='user_id'; protected $fillable=['username','password','role','is_active']; protected $hidden=['password','remember_token']; protected function casts(): array { return ['is_active'=>'boolean','password'=>'hashed']; } public function employee(): HasOne { return $this->hasOne(Employee::class,'user_id'); } public function isAdmin(): bool { return $this->role==='admin'; } }
