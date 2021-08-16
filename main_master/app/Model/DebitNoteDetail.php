<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\DebitNote;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;

class DebitNoteDetail extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'debit_note_id', 'item_id', 'item_condition', 'item_uom_id', 'discount_id', 'is_free', 'is_item_poi', 'promotion_id', 'item_qty', 'item_price', 'item_gross', 'item_discount_amount', 'item_net', 'item_vat', 'item_excise', 'item_grand_total', 'batch_number', 'reason',
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

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class,  'debit_note_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }

    public function discount()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class,  'discount_id', 'id');
    }

    public function promotion()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class,  'promotion_id', 'id');
    }
}
