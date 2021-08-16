<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class Planogram extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'start_date', 'end_date', 'status'
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

    public function planogramPost()
    {
        return $this->hasMany(PlanogramPost::class,  'planogram_id', 'id');
    }

    public function planogramCustomer()
    {
        return $this->hasMany(PlanogramCustomer::class,  'planogram_id', 'id');
    }

    public function planogramDistribution()
    {
        return $this->hasMany(PlanogramDistribution::class,  'planogram_id', 'id');
    }

    public function planogramImage()
    {
        return $this->hasMany(PlanogramImage::class,  'planogram_id', 'id');
    }

    public function getData()
    {
        // $this->planogramCustomer;
        // if (count($this->planogramCustomer)) {
        //     foreach($this->planogramCustomer as $key => $customer) {
        //         $this->planogramCustomer[$key]->customer = $customer;
        //     }
        // }
    }
}
