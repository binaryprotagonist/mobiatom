<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Depot;
use App\Model\Route;
use App\Model\PDPArea;

class Area extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'parent_id', 'area_name', 'node_level', 'status'
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

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children');
    }

    public function depot()
    {
        return $this->hasMany(Depot::class,  'area_id', 'id');
    }

    public function routes()
    {
        return $this->hasMany(Route::class,  'area_id', 'id');
    }

    public function PDPArea()
    {
        return $this->hasMany(PDPArea::class,  'area_id', 'id');
    }
}