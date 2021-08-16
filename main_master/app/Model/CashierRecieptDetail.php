<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class CashierRecieptDetail extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'cashier_reciept_detail';
    protected $fillable = [
        'uuid', 'cashier_reciept_id', 'payemnt_type', 'total_amount', 'actual_amount', 'variance'
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
	
	public function cashierreciept()
    {
        return $this->belongsTo(CashierReciept::class,  'cashier_reciept_id', 'id');
    }
}
