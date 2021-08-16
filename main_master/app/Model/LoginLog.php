<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class LoginLog extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'user_id', 'ip'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }
}