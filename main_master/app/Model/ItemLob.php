<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\PDPItemGroup;

class ItemLob extends Model
{
    protected $fillable = [
        'uuid', 'item_id', 'lob_id'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function lob()
    {
        return $this->belongsTo(Lob::class,  'lob_id', 'id');
    }
}
