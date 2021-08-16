<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\SalesmanLoad;

class SalesmanNumberRange extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'salesman_id', 'customer_from', 'customer_to', 'order_from', 'order_to', 'invoice_from', 'invoice_to', 'collection_from', 'collection_to', 'credit_note_from', 'credit_note_to', 'unload_from', 'unload_to'
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
	
    public function salesmanInfo()
    {
        return $this->belongsTo(SalesmanInfo::class,  'salesman_id', 'id');
    }
}