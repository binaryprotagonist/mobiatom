<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class AssignInventoryCustomer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'assign_inventory_id', 'customer_id'
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

    public function assignInventory()
    {
        return $this->belongsTo(AssignInventory::class,  'assign_inventory_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
}