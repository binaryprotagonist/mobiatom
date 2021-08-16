<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class LoadRequest extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    // protected $table = 'load_request';
    protected $fillable = [
        'uuid', 'organisation_id', 'route_id', 'salesman_id', 'load_number', 'load_type', 'load_date', 'status', 'current_stage', 'current_stage_comment', 'oddo_post_id', 'odoo_failed_response', 'approval_status', 'src_location'
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

    public function Organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function Route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function Salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function LoadRequestDetail()
    {
        return $this->hasMany(LoadRequestDetail::class,  'load_request_id', 'id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class,  'trip_id', 'id');
    }

    public function getSaveData()
    {
        $this->Route;
        $this->Salesman;
        $this->LoadRequestDetail;
        $this->trip;
        return $this;
    }

    public function getRequestData()
    {
        $this->Route;
        $this->Salesman;
        $this->LoadRequestDetail;
        $this->trip;
        
        /*if (is_object($this->trip)) {
            foreach ($this->trip as $key => $day) {
                $this->trip[$key]->depot = $day->depot;
                if (is_object($this->trip[$key]->depot)) {
                    foreach ($this->trip[$key]->depot as $k => $depot) {
                        $this->trip[$key]->depot[$k]->id = $depot->id;
                        $this->trip[$key]->depot[$k]->depot_code = $depot->depot_code;
                        $this->trip[$key]->depot[$k]->depot_name = $depot->depot_name;
                    }
                }`
            }
        }*/

        if (is_object($this->Route)) {
            $this->Route->depot;
        }

        if (is_object($this->Salesman)) {
            $this->Salesman->salesman_info;
        }

        if (is_object($this->load_request_detail)) {
            foreach ($this->load_request_detail as $key => $day) {
                $this->load_request_detail[$key]->item = $day->item;
                if (is_object($this->load_request_detail[$key]->item)) {
                    foreach ($this->load_request_detail[$key]->item as $k => $salesmanInfo) {
                        $this->load_request_detail[$key]->item[$k]->id = $salesmanInfo->id;
                        $this->load_request_detail[$key]->item[$k]->item_name = $salesmanInfo->item_name;
                        $this->load_request_detail[$key]->item[$k]->item_code = $salesmanInfo->item_code;
                    }
                }
            }
        }

        if (is_object($this->load_request_detail)) {
            foreach ($this->load_request_detail as $key => $day) {
                $this->load_request_detail[$key]->item_uom = $day->item_uom;
                if (is_object($this->load_request_detail[$key]->item_uom)) {
                    foreach ($this->load_request_detail[$key]->item_uom as $k => $salesmanInfo) {
                        $this->load_request_detail[$key]->item_uom[$k]->id = $salesmanInfo->id;
                        $this->load_request_detail[$key]->item_uom[$k]->name = $salesmanInfo->name;
                    }
                }
            }
        }

        return $this;
    }
}
