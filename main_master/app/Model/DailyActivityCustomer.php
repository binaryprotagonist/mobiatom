<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class DailyActivityCustomer extends Model
{
    protected $fillable = [
        'uuid', 'daily_activity_id', 'customer_id'
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

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function dailyActivityDetails()
    {
        return $this->hasMany(DailyActivityDetail::class,  'daily_activity_customer_id', 'id');
    }
}
