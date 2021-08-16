<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\DebitNote;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;

class DebitNoteListingfeeShelfrentRebatediscountDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'debit_note_id', 'customer_id', 'date', 'amount', 'item_name', 'type', 'vat_amount', 'total_amount',
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
}
