<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use App\Model\PermissionGroup;
use App\Model\OrganisationRoleHasPermission;

class PermissionExt extends Permission
{
    public function permissionGroup()
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id', 'id');
    }

    public function organisationRoleHasPermissions()
    {
        return $this->hasMany(OrganisationRoleHasPermission::class,  'permission_id', 'id');
    }
}
