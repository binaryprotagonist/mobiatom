<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;
use App\Model\Item;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioManagement extends Model
{
    use LogsActivity, Organisationid, SoftDeletes;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'code', 'start_date', 'end_date', 'status'
    ];

    protected $table = "portfolio_managements";

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

    public function portfolioManagementCustomer()
    {
        return $this->hasMany(PortfolioManagementCustomer::class, 'portfolio_management_id', 'id');
    }

    public function portfolioManagementItem()
    {
        return $this->hasMany(PortfolioManagementItem::class, 'portfolio_management_id', 'id');
    }

    public function getSaveData()
    {
        $this->portfolioManagementCustomer;
        if (count($this->portfolioManagementCustomer)) {
            foreach ($this->portfolioManagementCustomer as $key => $detail) {
                if (is_object($detail->user)) {
                    $this->portfolioManagementCustomer[$key]->user = $detail->user;
                }
            }
        }
        $this->portfolioManagementItem;
        return $this;
    }

}
