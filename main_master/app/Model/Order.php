<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\OrderType;
use App\Model\OrderDetail;
use App\User;
use App\Model\Depot;
use App\Model\Delivery;
use App\Model\Invoice;
use App\Model\PaymentTerm;
use App\Model\Lob;

class Order extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'customer_id', 'depot_id', 'order_type_id', 'salesman_id', 'route_id', 'customer_lop', 'order_number', 'order_date', 'due_date', 'delivery_date', 'payment_term_id', 'total_qty', 'total_discount_amount', 'total_net', 'total_vat', 'total_excise', 'grand_total', 'any_comment', 'delivered_qty', 'open_qty', 'current_stage', 'current_stage_comment', 'approval_status', 'sign_image', 'source', 'status'
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

    public function orderType()
    {
        return $this->belongsTo(OrderType::class,  'order_type_id', 'id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class,  'order_id', 'id');
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

    public function depot()
    {
        return $this->belongsTo(Depot::class,  'depot_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class,  'order_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class,  'order_id', 'id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class,  'payment_term_id', 'id');
    }
    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    }

    public function getSaveData()
    {
        $this->orderType;
        $this->paymentTerm;
        $this->orderDetails;
        if (count($this->orderDetails)) {

            foreach ($this->orderDetails as $key => $detail) {
                $this->orderDetails[$key]->item = $detail->item;
                $this->orderDetails[$key]->itemUom = $detail->itemUom;
            }
        }

        $this->customer;
        $this->salesman;
        $this->depot;
        $this->lob;
        return $this;
    }
}
