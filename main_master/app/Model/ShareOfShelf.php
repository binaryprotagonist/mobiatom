<?php

namespace App\Model;

use App\Http\Controllers\Api\ItemMajorCategoryController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Model\Software;
use App\Traits\Organisationid;
use App\User;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Item;
use App\Model\Distribution;
use App\Model\ItemUom;
use App\Model\Organisation;

class ShareOfShelf extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $table = 'share_of_shelves';

    protected $fillable = [
        'uuid', 'organisation_id', 'distribution_id', 'salesman_id', 'customer_id', 'item_id', 'total_number_of_facing', 'actual_number_of_facing', 'score'
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

    public function distribution()
    {
        return $this->belongsTo(Distribution::class,  'distribution_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(itemUom::class,  'item_uom_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }
}
