<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\DistributionCustomer;
use App\Model\DistributionModelStock;
use App\Model\DistributionModelStockDetails;
use App\Model\Survey;
use App\Model\PlanogramImage;
use App\User;

class Distribution extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'trip_id', 'customer_id', 'start_date', 'end_date', 'height', 'width', 'depth', 'status'
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

	public function distributionCustomer()
    {
        return $this->hasMany(DistributionCustomer::class,  'distribution_id', 'id')
        ->with('customer:id,firstname,lastname');
    }

    public function distributionModelStock()
    {
        return $this->hasMany(DistributionModelStock::class, 'distribution_id', 'id');
    }

    public function distributionModelStockDetails()
    {
        return $this->hasMany(DistributionModelStockDetails::class,  'distribution_id', 'id');
    }

    public function distributionSurvey()
    {
        return $this->hasMany(Survey::class,  'distribution_id', 'id');
    }

    public function planogramImage($planogram_id)
    {
        return $this->hasMany(PlanogramImage::class,  'distribution_id', 'id')
        ->where('planogram_id', $planogram_id)->get();
    }

    public function getSaveData()
    {
        $this->distributionCustomer;
        if (count($this->distributionCustomer)) {
            foreach($this->distributionCustomer as $key => $customer) {
                $this->distributionCustomer[$key]->customer = $customer;
            }
        }
        return $this;
    }

    // public function distributionCustomerWithCustomer()
    // {
    //     return $this->hasMany(DistributionCustomer::class,  'distribution_id', 'id')
    //     ->with('customer:id,firstname,lastname');
    // }
}
