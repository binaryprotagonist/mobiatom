<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;
use App\Model\Route;
use App\Model\SalesmanRole;
use App\Model\SalesmanType;
use App\Model\SalesmanLob;
use phpDocumentor\Reflection\Types\Self_;

class SalesmanInfo extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'user_id', 'route_id', 'region_id', 'salesman_type_id', 'salesman_role_id', 'category_id', 'salesman_helper_id', 'salesman_code', 'salesman_supervisor', 'date_of_joning', 'block_start_date', 'block_end_date', 'profile_image', 'incentive', 'status', 'current_stage', 'current_stage_comment', ''
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
        return $this->belongsTo(Organisation::class, 'organisation_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class,  'region_id', 'id');
    }

    public function salesmanRole()
    {
        return $this->belongsTo(SalesmanRole::class,  'salesman_role_id', 'id');
    }

    public function salesmanSupervisor()
    {
        return $this->belongsTo(User::class,  'salesman_supervisor', 'id');
    }

    public function salesmanType()
    {
        return $this->belongsTo(SalesmanType::class,  'salesman_type_id', 'id');
    }

    public function salesmanRange()
    {
        return $this->belongsTo(SalesmanNumberRange::class,  'id', 'salesman_id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }

    public function salesmanlob()
    {
        return $this->hasMany(SalesmanLob::class,  'salesman_info_id', 'id');
    }

    public function salesmanlobget()
    {
        return $this->hasMany(SalesmanLob::class,  'salesman_info_id', 'id')->with('lob:id,name');
    }

    public function salesmanHelper()
    {
        return $this->belongsTo(User::class, 'salesman_helper_id', 'id');
    }

    public function customerVisits()
    {
        return $this->hasMany(CustomerVisit::class, 'salesman_id', 'user_id');
    }

    public function salesmanInvoices()
    {
        return $this->hasMany(Invoice::class,  'salesman_id', 'user_id');
    }

    public function creditNoteSalesman()
    {
        return $this->hasMany(CreditNote::class,  'salesman_id', 'user_id');
    }

    public function collectionSalesmans()
    {
        return $this->hasMany(Collection::class,  'salesman_id', 'user_id');
    }

    public function getSaveData()
    {
        $this->user;
        $this->route;
        $this->salesmanRole;
        $this->salesmanType;
        $this->salesmanSupervisor;
        $this->salesmanRange;
        $this->salesmanlobget;
        $this->salesmanHelper;
        return $this;
    }
}
