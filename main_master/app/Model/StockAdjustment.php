<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\StockAdjustmentDetail;

class StockAdjustment extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    // protected $table = 'stock_adjustment';

    protected $fillable = [
        'uuid', 'organisation_id', 'account_id', 'warehouse_id', 'adjustment_mode', 'reason_id', 'reference_number', 'stock_adjustment_date', 'description', 'status'
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class,  'warehouse_id', 'id');
    }

    public function accounts()
    {
        return $this->belongsTo(Accounts::class,  'account_id', 'id');
    }
	
	public function stockadjustmentdetail()
    {
        return $this->hasMany(StockAdjustmentDetail::class,  'stock_adjustment_id', 'id');
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class,  'reason_id', 'id');
    }

    public function getSaveData()
    {
        $this->accounts;
        $this->warehouse;
        $this->reason;
        if (count($this->stockadjustmentdetail)) {
            foreach ($this->stockadjustmentdetail as $key => $detail) {
                $this->stockadjustmentdetail[$key]->item = $detail->item;
                $this->stockadjustmentdetail[$key]->itemUom = $detail->itemUom;
            }
        }
        return $this;
    }
}
