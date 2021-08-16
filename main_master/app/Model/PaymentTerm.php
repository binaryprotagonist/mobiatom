<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Order;
use App\Model\Delivery;
use App\Model\Invoice;

class PaymentTerm extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'number_of_days', 'status'
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

    public function organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function salesOrganisation()
    {
        return $this->belongsTo(SalesOrganisation::class,  'sales_organisation_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class,  'payment_term_id', 'id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class,  'payment_term_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class,  'payment_term_id', 'id');
    }
}
