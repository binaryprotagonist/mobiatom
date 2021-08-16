<?php

namespace App;

use App\Model\Collection;
use App\Model\Country;
use App\Model\CreditNote;
use App\Model\CustomerInfo;
use App\Model\CustomerVisit;
use App\Model\DebitNote;
use App\Model\Depot;
use App\Model\DistributionCustomer;
use App\Model\InviteUser;
use App\Model\Invoice;
use App\Model\LoginLog;
use App\Model\Notifications;
use App\Model\OrgAutoAppWorksflowActionLog;
use App\Model\Organisation;
use App\Model\OrganisationRole;
use App\Model\OutletProductCodeCustomer;
use App\Model\PricingPlan;
use App\Model\SalesmanInfo;
use App\Model\SurveyCustomer;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleApprovalUser;
use App\Traits\Organisationid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasRoles, SoftDeletes, LogsActivity, Organisationid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid', 'organisation_id', 'usertype', 'parent_id', 'firstname', 'lastname', 'email', 'password', 'api_token', 'email_verified_at', 'mobile', 'country_id', 'is_approved_by_admin', 'role_id', 'status'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function getDisplayNameAttribute()
    {
        return $this->firstname.' '.$this->lastname;  
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(OrganisationRole::class,  'role_id', 'id');
    }

    public function organisation_trim()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id')
            ->select('id', 'uuid', 'org_name');
    }

    public function country()
    {
        return $this->belongsTo(Country::class,  'country_id', 'id');
    }

    public function logInfo()
    {
        return $this->hasMany(OrgAutoAppWorksflowActionLog::class,  'log_for_id', 'id');
    }

    public function depot()
    {
        return $this->hasMany(Depot::class,  'user_id', 'id');
    }

    public function loginLog()
    {
        return $this->hasMany(LoginLog::class,  'user_id', 'id');
    }

    public function customerInfo()
    {
        return $this->hasOne(CustomerInfo::class,  'user_id', 'id');
    }

    public function salesmanInfo()
    {
        return $this->hasOne(SalesmanInfo::class,  'user_id', 'id');
    }

    public function pricingPlans()
    {
        return $this->hasMany(PricingPlan::class,  'customer_id', 'id');
    }

    public function outletProductCodeCustomers()
    {
        return $this->hasMany(OutletProductCodeCustomer::class,  'customer_id', 'id');
    }

    public function collectionCustomers()
    {
        return $this->hasMany(Collection::class,  'customer_id', 'id');
    }

    public function collectionSalesmans()
    {
        return $this->hasMany(Collection::class,  'salesman_id', 'id');
    }

    public function creditNoteCustomer()
    {
        return $this->hasMany(CreditNote::class,  'customer_id', 'id');
    }

    public function creditNoteSalesman()
    {
        return $this->hasMany(CreditNote::class,  'salesman_id', 'id');
    }

    public function debitNoteCustomer()
    {
        return $this->hasMany(DebitNote::class,  'customer_id', 'id');
    }

    public function debitNoteSalesman()
    {
        return $this->hasMany(DebitNote::class,  'salesman_id', 'id');
    }

    public function workFlowRuleApprovalUsers()
    {
        return $this->hasMany(WorkFlowRuleApprovalUser::class,  'user_id', 'id');
    }

    public function inviteNewUser()
    {
        return $this->hasOne(InviteUser::class,  'user_id', 'id');
    }

    public function invitedUser()
    {
        return $this->hasOne(InviteUser::class,  'invited_user_id', 'id');
    }

    public function workFlowObjectActions()
    {
        return $this->hasOne(WorkFlowObjectAction::class,  'user_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }

    public function customerVisitBySalesman()
    {
        return $this->hasMany(CustomerVisit::class,  'salesman_id', 'id');
    }
    public function customerVisitByCustomer()
    {
        return $this->hasMany(CustomerVisit::class,  'customer_id', 'id');
    }

    public function getName()
    {
        $name = '';
        
        if ($this->firstname) {
            $name = $this->firstname . ' ' . $this->lastname;
        }
        return $name;
    }

    public function surveyCustomer()
    {
        return $this->hasMany(SurveyCustomer::class,  'customer_id', 'id');
    }
    
    public function disctributionCustomer()
    {
        return $this->hasMany(DistributionCustomer::class,  'customer_id', 'id');
    }

    public function salesmanInvoices()
    {
        return $this->hasMany(Invoice::class,  'salesman_id', 'id');
    }

    /**
     * Get all of the notification's user.
     */
    public function notifications()
    {
        return $this->hasMany(Notifications::class, 'user_id', 'id');
        // return $this->morphMany(Notifications::class, 'commentable');
    }



    // public function salesmanCredit()
    // {
    //     return $this->hasMany(CreditNote::class,  'salesman_id', 'id');
    // }        

    // public function salesmanTotalCashSales(){
    //     return $this->salesmanInvoices()->sum('grand_total')->where('invoice_type',1);
    // }
}