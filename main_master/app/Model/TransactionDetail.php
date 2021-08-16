<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class TransactionDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'transaction_id', 'item_id', 'load_qty', 'transfer_in_qty', 'transfer_out_qty', 'request_qty', 'unload_qty', 'sales_qty', 'return_qty', 'free_qty', 'opening_qty', 'closing_qty'
    ];

    // protected $table = 'transaction_details';

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function warehouseDetailLogs()
    {
        return $this->hasMany(WarehouseDetailLog::class,  'warehouse_id', 'id');
    }

    public function Transaction()
    {
        return $this->belongsTo(Transaction::class,  'transaction_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

}
