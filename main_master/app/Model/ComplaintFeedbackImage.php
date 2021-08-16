<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Route;
use App\User;

class ComplaintFeedbackImage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'complaint_feedback_id', 'image_string'
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
    
    public function complaintFeedback()
    {
        return $this->belongsTo(ComplaintFeedback::class,  'complaint_feedback_id', 'id');
    }
}
