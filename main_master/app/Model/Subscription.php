<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscription extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'organisation_id', 'software_id', 'plan_id', 'offer_id', 'trial_period_start_date', 'trial_period_end_date', 'subscribe_after_trial', 'offer_start_date', 'offer_end_date', 'date_subscribed', 'valid_to', 'date_unsubscribed'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'plan_history_id', 'id');
    }
}
