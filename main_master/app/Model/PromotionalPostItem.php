<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PromotionalPostItem extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'promotional_post_id', 'item_id'
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

    public function promotionalPost()
    {
        return $this->belongsTo(PromotionalPost::class,  'promotional_post_id', 'id');
    }
    
    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }
}
