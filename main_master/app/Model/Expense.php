<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\ExpenseCategory;
use App\User;
use App\Model\Lob;

class Expense extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'expense_category_id', 'customer_id', 'reference', 'description', 'amount', 'expense_date', 'status'
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

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class,  'expense_category_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }
    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    }
}
