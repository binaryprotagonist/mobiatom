<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;
use App\User;

class Transaction extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'trip_id', 'salesman_id', 'route_id', 'transaction_type', 'transaction_date', 'transaction_time', 'organisation_id', 'source', 'Reference'
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

    public function transactiondetail()
    {
        return $this->hasMany(TransactionDetail::class,  'transaction_id', 'id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class,  'trip_id', 'id');
    }

    public function loadquantity()
    {
        return $this->hasMany(TransactionDetail::class,  'transaction_id', 'id')
                    ->selectRaw('transaction_id,item_id,
                                SUM(transaction_details.load_qty) as load_qty, 
                                SUM(transaction_details.sales_qty) as sales_qty,
                                SUM(transaction_details.return_qty) as return_qty,
                                SUM(transaction_details.unload_qty) as unload_qty' 
                                )
                    ->groupBy('item_id')->with('item:id,item_code,item_name,lower_unit_uom_id','item.itemUomLowerUnit:id,code,name');
    }


    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }
	
	public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
