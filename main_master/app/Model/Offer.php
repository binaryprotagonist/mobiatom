<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Offer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'offer_name', 'offer_start_date', 'offer_end_date', 'description', 'discount_amount', 'discount_percentage', 'duration_months', 'duration_end_date'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;
}
