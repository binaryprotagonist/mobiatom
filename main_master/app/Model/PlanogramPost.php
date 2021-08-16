<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class PlanogramPost extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'trip_id', 'name', 'start_date', 'end_date', 'customer_id', 'salesman_id', 'feedback', 'score', 'status'
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

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class,  'distribution_id', 'id');
    }

    public function planogram()
    {
        return $this->belongsTo(Planogram::class,  'planogram_id', 'id');
    }

    public function planogramPostBeforeImage()
    {
        return $this->hasMany(PlanogramPostBeforeImage::class, 'planogram_post_id', 'id');
    }

    public function planogramPostAfterImage()
    {
        return $this->hasMany(PlanogramPostAfterImage::class, 'planogram_post_id', 'id');
    }

    public function getSaveData()
    {
        $this->customer;
        $this->salesman;
        $this->distribution;
        $this->planogram;
        $this->planogramPostBeforeImage;
        $this->planogramPostAfterImage;
        return $this;
    }
}
