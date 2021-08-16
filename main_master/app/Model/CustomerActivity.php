<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class CustomerActivity extends Model
{
    use SoftDeletes, LogsActivity;
    
    // protected $table = 'customer_activity';

    protected $fillable = [
        'uuid', 'customer_visit_id', 'customer_id', 'activity_name', 'activity_action', 'start_time', 'end_time'
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
	
	public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
	
	public function CustomerVisit()
    {
        return $this->belongsTo(CustomerVisit::class,  'customer_visit_id', 'id');
    }

    public function getSaveData()
    {
        $this->customerInfo;
        $this->CustomerVisit;
        $this->user;
        return $this;
    }
}
