<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Order;
use App\Model\Delivery;
use App\Model\InvoiceDetail;
use App\Model\Collection;
use App\Model\CreditNote;
use App\Model\DebitNote;
use App\Model\PaymentTerm;
use App\Model\PurchaseOrderDetail;
use App\Model\Vendor;

class PurchaseOrder extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'vendor_id', 'reference', 'purchase_order', 'purchase_order_date', 'expected_delivery_date', 'customer_note', 'gross_total', 'vat_total', 'excise_total', 'net_total', 'discount_total'
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

    public function vendor()
    {
        return $this->belongsTo(Vendor::class,  'vendor_id', 'id');
    }

    public function purchaseOrderDetail()
    {
        return $this->hasMany(PurchaseOrderDetail::class,  'purchase_order_id', 'id');
    }
}
