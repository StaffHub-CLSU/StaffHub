<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $primaryKey = 'log_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'activity',
        'timestamp',
    ];
}
