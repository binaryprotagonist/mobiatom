<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyQuestionAnswerDetail extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'survey_id', 'survey_question_id', 'survey_question_answer_id', 'answer'
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

    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class,  'survey_question_id', 'id');
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class,  'survey_id', 'id');
    }

    public function surveyQuestionValue()
    {
        return $this->belongsTo(SurveyQuestionValue::class,  'answer', 'id');
    }
}