<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\CustomerInfo;
use App\Model\PDPCustomerCategory;

class CustomerCategory extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'customer_category_code', 'customer_category_name', 'parent_id', 'node_level', 'status'
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

    public function customerInfo()
    {
        return $this->hasMany(CustomerInfo::class,  'customer_category_id', 'id');
    }

    public function PDPCustomerCategories()
    {
        return $this->hasMany(PDPCustomerCategory::class,  'customer_category_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children')->select('id', 'uuid', 'customer_category_code as code', 'customer_category_name as name', 'parent_id', 'node_level', 'status');
    }

    public function childrenTrunck()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children')->select('id', 'uuid', 'customer_category_code as code', 'customer_category_name as name', 'parent_id', 'node_level', 'status');
    }
}
