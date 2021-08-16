<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class RoleMenu extends Model
{
    use LogsActivity;

    // protected $table = 'role_menu';

    protected $fillable = [
        'uuid', 'organisation_id', 'name'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;
}
