<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\SalesmanRole;
use App\Model\RoleMenu;

class SalesmanRoleMenu extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
     'organisation_id', 'salesman_role_id', 'menu_id', 'is_active'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function SalesmanRole()
    {
        return $this->belongsTo(SalesmanRole::class,  'salesman_role_id', 'id');
    }

    public function roleMenu()
    {
        return $this->belongsTo(RoleMenu::class,  'menu_id', 'id');
    }
}
