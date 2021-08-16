<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity; 
use App\Model\SalesmanInfo;

class SalesmanRole extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'code', 'name', 'status'
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

    public function salesmanInfos()
    {
        return $this->hasMany(SalesmanInfo::class,  'salesman_role_id', 'id');
    }
}
