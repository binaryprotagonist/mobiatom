<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class Goodreceiptnotedetail extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'good_receipt_note_detail';
    protected $fillable = [
        'uuid', 'good_receipt_note_id', 'item_id', 'item_uom_id', 'qty'
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
	
	public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }
}
