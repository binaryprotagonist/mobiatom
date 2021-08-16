<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\SalesPerson;
use App\Model\CustomerInfo;
use App\Model\EstimationDetail;
use App\User;

class Estimation extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $table = 'estimation';

    protected $fillable = [
        'uuid', 'organisation_id', 'customer_id', 'reference', 'estimate_code', 'estimate_date', 'expairy_date', 'salesperson_id', 'subject', 'customer_note', 'gross_total', 'vat', 'exise', 'net_total', 'discount', 'total'
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
	
	public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
	
	public function salesperson()
    {
        return $this->belongsTo(SalesPerson::class,  'salesperson_id', 'id');
    }
	
	public function estimationdetail()
    {
        return $this->hasMany(EstimationDetail::class,  'estimation_id', 'id');
    }
	
	public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }

	public function getSaveData()
    {
        $this->customer;
        $this->salesperson;
        $this->estimationdetail;

        if (count($this->estimationdetail)) {
            foreach ($this->estimationdetail as $key => $detail) {
                $this->estimationdetail[$key]->item = $detail->item;
                $this->estimationdetail[$key]->itemUom = $detail->itemUom;
            }
        }

        return $this;
    }
}
