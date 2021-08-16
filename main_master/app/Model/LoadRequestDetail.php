<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\User;

class LoadRequestDetail extends Model
{
    use SoftDeletes, LogsActivity;
    // protected $table = 'load_request_detail';
    protected $fillable = [
        'uuid', 'load_request_id', 'item_id', 'item_uom_id', 'qty'
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

    public function LoadRequest()
    {
        return $this->belongsTo(LoadRequest::class,  'load_request_id', 'id');
    }

    public function Item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function ItemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }
}
