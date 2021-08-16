<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Model\Software;

class SettingMenu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'software_id', 'name', 'is_active'
    ];

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id', 'id');
    }
}
