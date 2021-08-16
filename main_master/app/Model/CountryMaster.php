<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Organisationid;
use App\Model\Organisation;

class CountryMaster extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'country_code', 'dial_code', 'currency', 'currency_code', 'currency_symbol',
    ];

    public function organisation()
    {
        return $this->hasMany(Organisation::class,  'organisation_id', 'id');
    }
}