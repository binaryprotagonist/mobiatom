<?php

namespace App\Model;

use App\Model\JourneyPlanCustomer;
use App\Model\JourneyPlanDay;
use App\Model\JourneyPlanWeek;
use App\Model\Organisation;
use App\Model\Route;
use App\Model\SalesmanInfo;
use App\Traits\Organisationid;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class JourneyPlan extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'route_id', 'name', 'description', 'start_date', 'no_end_date', 'end_date', 'start_time', 'end_time', 'plan_type', 'week_1', 'week_2', 'week_3', 'week_4', 'week_5', 'current_stage', 'current_stage_comment', 'status', 'is_enforce'
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

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function merchandiser()
    {
        return $this->belongsTo(User::class,  'merchandiser_id', 'id');
    }

    public function journeyPlanWeeks()
    {
        return $this->hasMany(JourneyPlanWeek::class,  'journey_plan_id', 'id');
    }

    public function journeyPlanDays()
    {
        return $this->hasMany(JourneyPlanDay::class,  'journey_plan_id', 'id')->where('journey_plan_week_id', null);
    }

    public function journeyPlanCustomer()
    {
        return $this->hasMany(JourneyPlanCustomer::class,  'journey_plan_id', 'id');
    }

    public function customerVisits()
    {
        return $this->hasMany(CustomerVisit::class, 'journey_plan_id', 'id');
    }

    public function salesManJourneyPlan()
    {
        return $this->belongsTo(SalesmanInfo::class,  'merchandiser_id', 'user_id');
    }

    public function getSaveData()
    {
        if ($this->plan_type == 1) {
            $this->route;
            $this->journeyPlanDays;
            if (is_object($this->journeyPlanDays)) {
                foreach ($this->journeyPlanDays as $key => $day) {
                    $this->journeyPlanDays[$key]->journeyPlanCustomers = $day->journeyPlanCustomers;
                    if (is_object($this->journeyPlanDays[$key]->journeyPlanCustomers)) {
                        foreach ($this->journeyPlanDays[$key]->journeyPlanCustomers as $k => $customer) {
                            $this->journeyPlanDays[$key]->journeyPlanCustomers[$k]->customerInfo = $customer->customerInfo;
                        }
                    }
                }
                // if (is_object($this->journeyPlanDays->journeyPlanCustomers->customerInfo)) {
                //     $this->journeyPlanDays->journeyPlanCustomers->customerInfo;
                //     if (is_object($this->journeyPlanDays->journeyPlanCustomers->customerInfo->user)) {
                //         $this->journeyPlanDays->journeyPlanCustomers->customerInfo->user;
                //     }
                // }
            }
        } else {
            $this->journeyPlanWeeks;
            if (is_object($this->journeyPlanWeeks)) {
                foreach ($this->journeyPlanWeeks as $key => $week) {
                    $this->journeyPlanWeeks[$key]->journeyPlanCustomers = $week->journeyPlanCustomers;
                    if (is_object($this->journeyPlanWeeks[$key]->journeyPlanCustomers)) {
                        foreach ($this->journeyPlanWeeks[$key]->journeyPlanCustomers as $k => $customer) {
                            $this->journeyPlanWeeks[$key]->journeyPlanCustomers[$k]->customerInfo = $customer->customerInfo;
                        }
                    }
                }

                // if (is_object($this->journeyPlanWeeks->journeyPlanCustomers->customerInfo)) {
                //     $this->journeyPlanWeeks->journeyPlanCustomers->customerInfo;
                //     if (is_object($this->journeyPlanWeeks->journeyPlanCustomers->customerInfo->user)) {
                //         $this->journeyPlanWeeks->journeyPlanCustomers->customerInfo->user;
                //     }
                // }
            }
        }

        return $this;
    }
}
