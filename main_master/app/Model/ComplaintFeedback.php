<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Depot;
use App\Model\Route;
use App\User;

class ComplaintFeedback extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'route_id', 'trip_id', 'salesman_id', 'customer_id', 'complaint_id', 'title', 'item_id', 'description',  'status', 'current_stage', 'current_stage_comment'
    ];

    protected $table = "complaint_feedbacks";

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

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
    
    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function complaintFeedbackImage()
    {
        return $this->hasMany(ComplaintFeedbackImage::class,  'complaint_feedback_id', 'id');
    }

    public function getSaveData()
    {
        $this->item;
        $this->salesman;
        $this->customer;
        $this->route;
        $this->complaintFeedbackImage;
        return $this;
    }
}
