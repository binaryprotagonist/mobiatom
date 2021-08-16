<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class PlanogramCustomer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'planogram_id', 'customer_id'
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
    
    public function planogram()
    {
        return $this->belongsTo(Planogram::class,  'planogram_id', 'id');
    }

    public function planogramDistribution()
    {
        return $this->hasMany(PlanogramDistribution::class,  'planogram_customer_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
    
    public function disctributionCustomer()
    {
        return $this->hasMany(DistributionCustomer::class,  'customer_id', 'customer_id');
    }
}
