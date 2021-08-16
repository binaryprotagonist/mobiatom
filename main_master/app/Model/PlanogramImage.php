<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class PlanogramImage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'planogram_id', 'planogram_distribution_id', 'image_string'
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
    
    public function planogram()
    {
        return $this->belongsTo(Planogram::class,  'planogram_id', 'id');
    }

    public function planogramDistribution()
    {
        return $this->belongsTo(PlanogramDistribution::class,  'planogram_distribution_id', 'id');
    }
}
