<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Customer;
use App\User;
use App\Model\SalesmanInfo;

class CustomerMerchandizer extends Model
{
    use LogsActivity;

    // protected $table = 'customer_merchandizer';
    
    protected $fillable = [
        'uuid', 'user_id', 'merchandizer_id'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class,  'user_id', 'id');
    }

    public function customers()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }

    public function customerVisitByCustomer()
    {
        return $this->hasMany(CustomerVisit::class,  'customer_id', 'user_id');
    }

    public function customerVisitBySalesman()
    {
        return $this->hasMany(CustomerVisit::class,  'salesman_id', 'merchandizer_id');
    }

    public function merchandizer()
    {
        return $this->belongsTo(User::class,  'merchandizer_id', 'id')->select('id', 'firstname', 'lastname','email');
    }

    public function deleteMerchandizer($user_id) {
        CustomerMerchandizer::where('user_id', $user_id)->delete();
    }

    public function planogramCustomer()
    {
        return $this->hasMany(PlanogramCustomer::class, 'customer_id', 'user_id');
    }
}
