<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyQuestionAnswer extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'survey_id', 'survey_type_id', 'salesman_id', 'customer_id', 'customer_name', 'email', 'phone'
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
    
    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }
    
    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function surveyType()
    {
        return $this->belongsTo(SurveyType::class,  'survey_type_id', 'id');
    }

    public function surveyQuestionAnswerDetail()
    {
        return $this->hasMany(SurveyQuestionAnswerDetail::class,  'survey_question_answer_id', 'id');
    }
}
