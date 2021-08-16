<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;

class Theme extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'name', 'status'
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

    public function organisationTheme()
    {
        return $this->belongsTo(OrganisationTheme::class, 'id', 'theme_id');
    }
}
