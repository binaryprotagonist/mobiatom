<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Order;
use App\Model\OrderType;
use App\Model\Delivery;
use App\Model\InvoiceDetail;
use App\Model\Collection;
use App\Model\CreditNote;
use App\Model\DebitNote;
use App\Model\PaymentTerm;
use App\Model\InvoiceReminder;
use App\Model\Lob;
use App\User;

class Invoice extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'order_id', 'delivery_id', 'depot_id', 'customer_id','odoo_failed_response', 'salesman_id', 'route_id', 'odoo_id','invoice_type', 'invoice_number', 'invoice_date', 'payment_term_id', 'total_qty', 'total_discount_amount', 'total_net', 'total_vat', 'total_excise', 'grand_total', 'current_stage', 'current_stage', 'payment_received', 'source', 'is_premium_invoice', 'approval_status', 'status','reason', 'lob_id', 'customer_lpo', 'is_exchange', 'exchange_number', 'pending_credit'
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

    public function delivery()
    {
        return $this->belongsTo(Delivery::class,  'delivery_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(InvoiceDetail::class,  'invoice_id', 'id');
    }

    public function collection()
    {
        return $this->hasMany(Collection::class,  'invoice_id', 'id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class,  'invoice_id', 'id');
    }

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class,  'invoice_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function salesmanUser()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class,  'payment_term_id', 'id');
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class,  'order_type_id', 'id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class,  'depot_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function invoiceReminder()
    {
        return $this->belongsTo(InvoiceReminder::class, 'id', 'invoice_id');
    }

    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    }

    public function customerInfoDetails()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'user_id');
    }

    public function invoicedetail()
    {
        return $this->hasMany(InvoiceDetail::class,  'invoice_id', 'id')
            ->selectRaw(
                'invoice_id,item_id,
                                SUM(invoice_details.item_gross) as Total_invoice_sales,
                                SUM(invoice_details.item_net) as Total_invoice_net'
            )
            ->groupBy('item_id')->with('item:id,item_code,item_name,item_major_category_id', 'item.itemMajorCategory:id,name');
    }

    public function getSaveData()
    {
        $this->user;
        $this->depot;
        $this->order;
        if (is_object($this->order)) {
            $this->order->orderDetails;
        }
        $this->invoices;
        if (count($this->invoices)) {
            foreach ($this->invoices as $key => $invoiced) {
                $this->invoices[$key]->item = $invoiced->item;
                $this->invoices[$key]->itemUom = $invoiced->itemUom;
            }
        }
        $this->orderType;
        $this->invoiceReminder;
        if (is_object($this->invoiceReminder)) {
            $this->invoiceReminder->invoiceReminderDetails;
        }
        $this->lob;
    }
}
