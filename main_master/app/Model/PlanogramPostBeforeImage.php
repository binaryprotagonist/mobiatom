<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class PlanogramPostBeforeImage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'planogram_post_id', 'image_string'
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

    public function planogramPost()
    {
        return $this->belongsTo(PlanogramPost::class,  'planogram_post_id', 'id');
    }
}
