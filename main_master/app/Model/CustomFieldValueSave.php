<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\CustomField;

class CustomFieldValueSave extends Model
{
    protected $fillable = [
        'record_id', 'module_id', 'custom_field_id', 'custom_field_value'
    ];

    public function customField()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'record_id', 'id');
    }
}