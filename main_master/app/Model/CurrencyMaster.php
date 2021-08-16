<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrencyMaster extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'symbol', 'name', 'code', 'name_plural', 'symbol_native', 'decimal_digits', 'rounding'
    ];

}