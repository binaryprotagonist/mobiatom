<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class AssetTracking extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'customer_id', 'title', 'code', 'start_date', 'end_date', 'description', 'model_name', 'barcode', 'category', 'location', 'lat', 'lng', 'area', 'parent_id', 'wroker', 'additional_wroker', 'team', 'vendors', 'purchase_date', 'placed_in_service', 'purchase_price', 'warranty_expiration', 'residual_price', 'additional_information', 'useful_life', 'image'
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

    public function surveyCustomer()
    {
        return $this->hasMany(SurveyCustomer::class,  'customer_id', 'customer_id');
    }
}