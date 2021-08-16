<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Invoice;
use App\User;
use App\Model\SalesTargetDetail;
use App\Model\SalesItemTargetDetail;

class SalesTarget extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'TargetEntity', 'TargetName', 'TargetOwnerId', 'StartDate', 'EndDate', 'Applyon', 'TargetType', 'TargetVariance', 'CommissionType', 'status'
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class,  'order_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function salesTargetDetails()
    {
        return $this->hasMany(SalesTargetDetail::class,  'sales_target_id', 'id');
    }

    public function SalesItemTargetDetail()
    {
        return $this->hasMany(SalesItemTargetDetail::class,  'sales_target_id', 'id');
    }

    public function getSaveData()
    {
        $this->invoice;
        $this->customer;
        $this->salesman;
        if (count($this->SalesItemTargetDetail)) {
            foreach ($this->SalesItemTargetDetail as $k => $itemDetail) {
                $this->SalesItemTargetDetail[$k]->salesTargetDetails = $itemDetail->salesTargetDetails;
                $this->SalesItemTargetDetail[$k]->item = $itemDetail->item;
                $this->SalesItemTargetDetail[$k]->itemUom = $itemDetail->itemUom;
            }
        }
        return $this;
    }
}
