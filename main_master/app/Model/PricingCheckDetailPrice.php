<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PricingCheckDetailPrice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'pricing_check_id', 'price', 'srp'
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

    public function pricingCheck()
    {
        return $this->belongsTo(PricingCheck::class,  'pricing_check_id', 'id');
    }

    public function pricingCheckDetail()
    {
        return $this->belongsTo(PricingCheckDetail::class,  'pricing_check_detail_id', 'id');
    }
}
