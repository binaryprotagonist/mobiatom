<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Region;
use App\Model\Route;
use App\Model\SalesOrganisation;
use App\Model\Channel;
use App\Model\CustomerGroup;
use App\Model\CustomerCategory;
use App\Model\CustomerType;
use App\Model\JourneyPlanCustomer;
use App\User;
use App\Model\Order;
use App\PDPCustomer;
use Psy\CodeCleaner\ReturnTypePass;

class SalesmanLob extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'salesman_info_id', 'lob_id'
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

    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    } 

    public function getSaveData()
    {     
        $this->lob;

        return $this;
    }
   
}
