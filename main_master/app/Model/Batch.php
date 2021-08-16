<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Item;

class Batch extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'item_id', 'batch_number', 'manufacturing_date', 'expiry_date', 'manufactured_by', 'qty', 'current_in_stock', 'stock_out_sequence', 'status',
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
}
