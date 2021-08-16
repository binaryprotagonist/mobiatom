<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class CashierReciept extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $table = 'cashier_reciept';

    protected $fillable = [
        'uuid', 'organisation_id', 'cashier_reciept_number', 'route_id', 'salesman_id', 'date', 'slip_number', 'bank', 'slip_date', 'total_amount', 'actual_amount', 'variance', 'payment_type', 'status'
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

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function cashierrecieptdetail()
    {
        return $this->hasMany(CashierRecieptDetail::class,  'cashier_reciept_id', 'id');
    }

    public function getSaveData()
    {
        $this->route;
        $this->salesman;
        $this->cashierrecieptdetail;
        return $this;
    }
}
