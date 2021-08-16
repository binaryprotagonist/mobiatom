<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\DailyActivity;
use App\Model\SupervisorCategory;

class DailyActivityDetail extends Model
{
    protected $fillable = [
        'uuid', 'daily_activity_id', 'supervisor_category_id', 'supervisor_category_status', 'shelf_display', 'off_shelf_display', 'opportunity', 'out_of_stock', 'remarks', 'status'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function dailyActivity()
    {
        return $this->belongsTo(DailyActivity::class,  'daily_activity_id', 'id');
    }

    public function supervisorCategory()
    {
        return $this->belongsTo(SupervisorCategory::class,  'supervisor_category_id', 'id');
    }
}
