<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Software extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'details', 'slug', 'access_link', 'status'
    ];

    protected $table = "softwares";

    public function plan()
    {
        return $this->hasMany(Plan::class, 'software_id', 'id');
    }
}
