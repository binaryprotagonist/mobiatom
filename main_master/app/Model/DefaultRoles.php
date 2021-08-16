<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class DefaultRoles extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'name', 'guard_name'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

}
