<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class ReportModule extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    protected $table = 'report_module';
    protected $fillable = [
        'uuid', 'organisation_id', 'parent_id', 'module_name', 'is_new'
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
	public function parentmodule()
    {
        return $this->belongsTo(ReportModule::class,  'parent_id', 'id');
    }
}
