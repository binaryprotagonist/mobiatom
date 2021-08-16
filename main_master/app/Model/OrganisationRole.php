<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\OrganisationRoleHasPermission;
use App\Model\WorkFlowRuleApprovalRole;
use App\Model\InviteUser;

class OrganisationRole extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'description', 'status'
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

    public function organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function organisationRoleHasPermissions()
    {
        return $this->hasMany(OrganisationRoleHasPermission::class,  'organisation_role_id', 'id');
    }

    public function workFlowRuleApprovalRoles()
    {
        return $this->hasMany(WorkFlowRuleApprovalRole::class,  'organisation_role_id', 'id');
    }

    public function inviteUser()
    {
        return $this->hasOne(InviteUser::class,  'organisation_role_id', 'id');
    }
}