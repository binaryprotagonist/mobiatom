<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\CountryMaster;
use App\Model\OrgAutoAppWorksflowActionLog;
use App\Model\Country;
use App\Model\CustomerCategory;
use App\User;
use App\Model\CustomerType;
use App\Model\CustomerInfo;
use App\Model\Region;
use App\Model\Area;
use App\Model\Depot;
use App\Model\Route;
use App\Model\RouteTemplate;
use App\Model\Van;
use App\Model\VanCategory;
use App\Model\VanType;
use App\Model\ItemMajorCategory;
use App\Model\ItemUom;
use App\Model\Item;
use App\Model\OutletProductCode;
use App\Model\CombinationPlanKey;
use App\Model\SalesOrganisation;
use App\Model\Channel;
use App\Model\CodeSetting;
use App\Model\Order;
use App\Model\Delivery;
use App\Model\Invoice;
use App\Model\Collection;
use App\Model\CreditNote;
use App\Model\PaymentTerm;
use App\Model\RouteItemGrouping;
use App\Model\Warehouse;
use App\Model\BankInformation;
use App\Model\ExpenseCategory;
use App\Model\Expense;

class Organisation extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'org_name', 'reg_software_id', 'org_company_id', 'org_tax_id', 'org_street1', 'org_street2', 'org_city', 'org_state', 'org_country_id', 'org_postal', 'org_phone', 'org_contact_person', 'org_contact_person_number', 'org_currency', 'org_fasical_year', 'is_batch_enabled', 'is_credit_limit_enabled', 'gstin_number', 'gst_reg_date', 'is_auto_approval_set', 'org_logo', 'count_user'
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

    public function countryInfo()
    {
        return $this->belongsTo(CountryMaster::class,  'org_country_id', 'id');
    }

    public function countries()
    {
        return $this->hasMany(Country::class,  'organisation_id', 'id');
    }

    public function orgAutoAppWorksflowActionLogs()
    {
        return $this->hasMany(OrgAutoAppWorksflowActionLog::class,  'organisation_id', 'id');
    }

    public function customerCategories()
    {
        return $this->hasMany(CustomerCategory::class,  'organisation_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class,  'organisation_id', 'id');
    }

    public function customerTypes()
    {
        return $this->hasMany(CustomerType::class,  'organisation_id', 'id');
    }

    public function customerInfo()
    {
        return $this->hasMany(CustomerInfo::class,  'organisation_id', 'id');
    }

    public function regions()
    {
        return $this->hasMany(Region::class,  'organisation_id', 'id');
    }

    public function depots()
    {
        return $this->hasMany(Depot::class,  'organisation_id', 'id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class,  'organisation_id', 'id');
    }

    public function routes()
    {
        return $this->hasMany(Route::class,  'organisation_id', 'id');
    }

    public function routeTemplates()
    {
        return $this->hasMany(RouteTemplate::class,  'organisation_id', 'id');
    }

    public function vans()
    {
        return $this->hasMany(Van::class,  'organisation_id', 'id');
    }

    public function vanCategorys()
    {
        return $this->hasMany(VanCategory::class,  'organisation_id', 'id');
    }

    public function vanTypes()
    {
        return $this->hasMany(VanType::class,  'organisation_id', 'id');
    }

    public function ItemMajorCategories()
    {
        return $this->hasMany(ItemMajorCategory::class,  'organisation_id', 'id');
    }

    public function itemUoms()
    {
        return $this->hasMany(ItemUom::class,  'organisation_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(Item::class,  'organisation_id', 'id');
    }

    public function outletProductCodes()
    {
        return $this->hasMany(OutletProductCode::class,  'organisation_id', 'id');
    }

    public function CombinationPlanKey()
    {
        return $this->hasMany(CombinationPlanKey::class,  'organisation_id', 'id');
    }

    public function salesOrganisations()
    {
        return $this->hasMany(SalesOrganisation::class,  'organisation_id', 'id');
    }

    public function channels()
    {
        return $this->hasMany(Channel::class,  'organisation_id', 'id');
    }

    public function codeSettings()
    {
        return $this->hasMany(CodeSetting::class,  'organisation_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class,  'organisation_id', 'id');
    }

    public function deliveries()
    {
        return $this->hasMany(Order::class,  'organisation_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class,  'organisation_id', 'id');
    }

    public function collections()
    {
        return $this->hasMany(Collection::class,  'organisation_id', 'id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class,  'organisation_id', 'id');
    }

    public function paymentTerms()
    {
        return $this->hasMany(PaymentTerm::class,  'organisation_id', 'id');
    }

    public function routeItemGroupings()
    {
        return $this->hasMany(RouteItemGrouping::class,  'organisation_id', 'id');
    }

    public function warehouse()
    {
        return $this->hasMany(Warehouse::class,  'organisation_id', 'id');
    }

    public function bankInformations()
    {
        return $this->hasMany(BankInformation::class,  'organisation_id', 'id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class,  'organisation_id', 'id');
    }

    public function ExpenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class,  'organisation_id', 'id');
    }
    
    public function organisationPurchasePlan()
    {
        return $this->hasMany(OrganisationPurchasePlan::class,  'organisation_id', 'id');
    }
}
