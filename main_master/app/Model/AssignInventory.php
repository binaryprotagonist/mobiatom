<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class AssignInventory extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'activity_name', 'valid_from', 'valid_to', 'status'
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

    public function assignInventoryCustomer()
    {
        return $this->hasMany(AssignInventoryCustomer::class,  'assign_inventory_id', 'id');
    }

    public function assignInventoryDetails()
    {
        return $this->hasMany(AssignInventoryDetails::class,  'assign_inventory_id', 'id');
    }
    
    public function assignInventoryPost()
    {
        return $this->hasMany(AssignInventoryPost::class,  'assign_inventory_id', 'id');
    }

    public function getSaveData()
    {
        $this->assignInventoryCustomer;
        foreach ($this->assignInventoryCustomer as $key => $customer) {
            $this->assignInventoryCustomer[$key]->customer = $customer->customer;
        }
        // if (is_object($this->assignInventoryCustomer->customer)) {
        //     $this->assignInventoryCustomer->customer;
        // }
        $this->assignInventoryDetails;
        $this->assignInventoryPost;
        return $this;
    }

}