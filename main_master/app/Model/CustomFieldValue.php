<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\CustomField;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'custom_field_id', 'field_value'
    ];

    public function customField()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id', 'id');
    }
}