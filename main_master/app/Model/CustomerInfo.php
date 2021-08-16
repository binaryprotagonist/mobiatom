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

class CustomerInfo extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'user_id', 'region_id', 'route_id', 'customer_group_id', 'sales_organisation_id', 'channel_id', 'customer_code', 'erp_code', 'customer_type_id', 'payment_term_id', 'customer_category_id', 'customer_address_1', 'customer_address_2', 'customer_city', 'customer_state', 'customer_zipcode', 'customer_phone', 'customer_address_1_lat', 'customer_address_1_lang', 'customer_address_2_lat', 'customer_address_2_lang', 'balance', 'credit_limit', 'credit_days', 'due_on', 'ship_to_party', 'sold_to_party', 'payer', 'bill_to_payer', 'current_stage', 'current_stage_comment', 'status', 'is_lob', 'expired_date'
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

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function salesOrganisation()
    {
        return $this->belongsTo(SalesOrganisation::class, 'sales_organisation_id', 'id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id', 'id');
    }

    public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id', 'id');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function merchandiser()
    {
        return $this->belongsTo(User::class, 'merchandiser_id', 'id');
    }

    public function journeyPlanCustomers()
    {
        return $this->hasMany(JourneyPlanCustomer::class,  'customer_id', 'id');
    }

    public function shipToParty()
    {
        return $this->belongsTo(CustomerInfo::class, 'ship_to_party', 'id');
    }

    public function soldToParty()
    {
        return $this->belongsTo(CustomerInfo::class, 'sold_to_party', 'id');
    }

    public function payer()
    {
        return $this->belongsTo(CustomerInfo::class, 'payer', 'id');
    }

    public function billToPayer()
    {
        return $this->belongsTo(CustomerInfo::class, 'bill_to_payer', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class,  'customer_id', 'id');
    }

    public function PDPCustomer()
    {
        return $this->hasMany(PDPCustomer::class,  'customer_id', 'id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class,  'payment_term_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }

    public function assignInventoryCustomer()
    {
        return $this->hasMany(AssignInventoryCustomer::class, 'customer_id', 'user_id');
    }

    public function customerDocument()
    {
        return $this->hasMany(CustomerDocument::class, 'customer_id', 'user_id');
    }

    public function distributionCustomer()
    {
        return $this->hasMany(DistributionCustomer::class, 'customer_id', 'user_id');
    }

    public function planogramCustomer()
    {
        return $this->hasMany(PlanogramCustomer::class, 'customer_id', 'user_id');
    }

    public function assetTracking()
    {
        return $this->hasMany(AssetTracking::class, 'customer_id', 'user_id');
    }

    public function surveyCustomer()
    {
        return $this->hasMany(SurveyCustomer::class, 'customer_id', 'user_id');
    }

    public function portfolioManagementCustomer()
    {
        return $this->hasMany(PortfolioManagementCustomer::class, 'user_id', 'user_id');
    }

    public function salesmanActivityProfiles()
    {
        return $this->hasMany(SalesmanActivityProfile::class, 'customer_id', 'user_id');
    }

    public function customerVisit()
    {
        return $this->hasMany(CustomerVisit::class, 'customer_id', 'user_id');
    }

    public function customerMerchandiser()
    {
        return $this->hasMany(CustomerMerchandizer::class, 'user_id', 'user_id');
    }

    public function customerlob()
    {
        return $this->hasMany(CustomerLob::class,  'customer_info_id', 'id');
    }

    public function user_country()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->select('id','organisation_id','country_id')->with('country:id,name');
    } 

    public function getAddress()
    {
        $location = '';

        if ($this->customer_address_1) {
            $location .= $this->customer_address_1;
        }

        if ($this->customer_address_2) {
            $location .= ' ' . $this->customer_address_2;
        }

        if ($this->customer_city) {
            $location .= ' ,' . $this->customer_city;
        }
        if ($this->customer_state) {
            $location .= ' ,' . $this->customer_state;
        }
        if ($this->customer_zipcode) {
            $location .= ' ,' . $this->customer_zipcode;
        }
    }

    public function getSaveData()
    {
        $this->user;
        $this->route;
        $this->channel;
        $this->merchandiser;
        $this->region;
        $this->customerGroup;
        $this->salesOrganisation;
        $this->shipToParty;
        if (is_object($this->shipToParty)) {
            $this->shipToParty->user;
        }
        $this->soldToParty;
        if (is_object($this->soldToParty)) {
            $this->soldToParty->user;
        }
        // $this->payer;
        // if (is_object($this->payer)) {
        //     $this->payer->user;
        // }
        $this->billToPayer;
        if (is_object($this->billToPayer)) {
            $this->billToPayer->user;
        }
        $this->customerlob;
        $this->user_country;

        return $this;
    }
}
