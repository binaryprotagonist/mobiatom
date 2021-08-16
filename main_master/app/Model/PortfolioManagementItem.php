<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\PortfolioManagement;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioManagementItem extends Model
{
    use LogsActivity, SoftDeletes;
    
    protected $fillable = [
        'uuid', 'portfolio_management_id', 'item_id', 'listing_fees', 'store_price'
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

    public function portfolioManagement()
    {
        return $this->belongsTo(PortfolioManagement::class, 'portfolio_management_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
