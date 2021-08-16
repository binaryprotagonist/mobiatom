<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\PortfolioManagement;
use App\Model\PricingCheckDetailPrice;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class PortfolioManagementCustomer extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid', 'portfolio_management_id', 'user_id'
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function portfolioManagement()
    {
        return $this->belongsTo(PortfolioManagement::class, 'portfolio_management_id', 'id');
    }

    public function priceCheck()
    {
        return $this->hasMany(PricingCheck::class, 'customer_id', 'user_id')
                ->with(
                    'pricingDetails:id,pricing_check_id,item_id', 
                    'pricingDetails.pricingCheckDetailPrice:id,pricing_check_id,pricing_check_detail_id,srp,price'
                );
    }
}
