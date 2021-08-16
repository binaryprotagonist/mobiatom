<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyCustomer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'survey_id', 'survey_type_id', 'customer_id'
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
    
    public function surveyType()
    {
        return $this->belongsTo(SurveyType::class,  'survey_type_id', 'id');
    }
    
    public function survey()
    {
        return $this->belongsTo(Survey::class,  'survey_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function assetTracking()
    {
        return $this->hasMany(AssetTracking::class,  'customer_id', 'customer_id');
    }
}
