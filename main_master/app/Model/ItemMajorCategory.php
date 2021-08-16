<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\PDPItemMajorCategory;

class ItemMajorCategory extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'parent_id', 'name', 'node_level', 'status'
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

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function PDPItemMajorCategories()
    {
        return $this->hasMany(PDPItemMajorCategory::class,  'item_major_category_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'item_major_category_id', 'id');
    }
}