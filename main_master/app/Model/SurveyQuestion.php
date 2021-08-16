<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Organisationid;
use App\Model\Organisation;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyQuestion extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'survey_id', 'question', 'question_type'
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

    public function survey()
    {
        return $this->belongsTo(Survey::class,  'survey_id', 'id');
    }

    public function surveyQuestionValue()
    {
        return $this->hasMany(SurveyQuestionValue::class,  'survey_question_id', 'id');
    }

    public function surveyQuestionAnswer()
    {
        return $this->hasMany(SurveyQuestionAnswer::class,  'survey_id', 'id');
    }
}
