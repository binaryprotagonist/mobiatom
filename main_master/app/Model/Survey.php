<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;

class Survey extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'survey_type_id', 'question_type', 'name', 'start_date', 'end_date'
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
    
    public function surveyType()
    {
        return $this->belongsTo(SurveyType::class,  'survey_type_id', 'id');
    }

    public function surveyCustomer()
    {
        return $this->hasMany(SurveyCustomer::class,  'survey_id', 'id');
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class, 'distribution_id', 'id');
    }

    public function surveyQuestion()
    {
        return $this->hasMany(SurveyQuestion::class, 'survey_id', 'id');
    }

}
