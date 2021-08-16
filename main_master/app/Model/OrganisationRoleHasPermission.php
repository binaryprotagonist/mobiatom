<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\OrganisationRole;
use App\Model\PermissionExt;

class OrganisationRoleHasPermission extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'organisation_role_id', 'permission_id'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function organisationRole()
    {
        return $this->belongsTo(OrganisationRole::class,  'organisation_role_id', 'id');
    }

    public function permission()
    {
        return $this->belongsTo(PermissionExt::class,  'permission_id', 'id');
    }
}
