<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Organisationid;
use App\Model\Organisation;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyQuestionValue extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'survey_id', 'survey_question_id', 'question_value'
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

    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class,  'survey_question_id', 'id');
    }
}
