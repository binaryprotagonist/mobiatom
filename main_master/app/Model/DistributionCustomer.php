<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;
use App\Model\Distribution;

class DistributionCustomer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'customer_id', 'distribution_id'
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

	public function distribution()
    {
        return $this->belongsTo(Distribution::class,  'distribution_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
}
