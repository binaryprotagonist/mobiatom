<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\RouteItemGrouping;
use App\Model\Item;

class RouteItemGroupingDetail extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'uuid', 'route_item_grouping_id', 'item_id'
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

    public function routeItemGrouping()
    {
        return $this->belongsTo(RouteItemGrouping::class, 'route_item_grouping_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }    
}
