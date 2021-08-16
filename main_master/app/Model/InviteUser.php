<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\OrganisationRole;
use App\User;

class InviteUser extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'user_id', 'invited_user_id', 'role_id', 'status'
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
    
    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class,  'invited_user_id', 'id');
    }

    public function organisationRole()
    {
        return $this->belongsTo(OrganisationRole::class,  'role_id', 'id');
    }

    public function getSaveData()
    {
        $this->user;
        $this->invitedUser;
        $this->organisationRole;
        return $this;
    }
}
