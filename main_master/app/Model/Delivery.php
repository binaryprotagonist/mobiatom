<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Order;
use App\User;
use App\Model\DeliveryDetail;
use App\Model\Invoice;
use App\Model\PaymentTerm;
use App\Model\Lob;

class Delivery extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'order_id', 'customer_id', 'salesman_id', 'route_id', 'delivery_type', 'delivery_type_source', 'delivery_number', 'delivery_date', 'delivery_time', 'delivery_weight', 'payment_term_id', 'total_qty', 'total_discount_amount', 'total_net', 'total_vat', 'total_excise', 'grand_total', 'current_stage', 'current_stage_comment', 'source', 'status', 'approval_status'
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

    public function order()
    {
        return $this->belongsTo(Order::class,  'order_id', 'id');
    }

    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function salesmanInfo()
    {
        return $this->belongsTo(SalesmanInfo::class,  'salesman_id', 'id');
    }

    public function deliveryDetails()
    {
        return $this->hasMany(DeliveryDetail::class,  'delivery_id', 'id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class,  'delivery_id', 'id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class,  'payment_term_id', 'id');
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class,  'delivery_type', 'id');
    }

    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    }

    public function getSaveData()
    {
        $this->order;
        $this->salesman;
        $this->customer;
        $this->invoice;
        $this->paymentTerm;
        $this->deliveryDetails;

        if (is_object($this->deliveryDetails)) {
            foreach ($this->deliveryDetails as $key => $details) {
                $this->deliveryDetails[$key]->item = $details->item;
                $this->deliveryDetails->itemUom = $details->itemUom;
            }
        }
        $this->lob;

        return $this;
    }
}
