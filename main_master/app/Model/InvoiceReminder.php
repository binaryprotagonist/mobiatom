<?php

namespace App\Model;

use App\Imports\InvoiceImport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;

class InvoiceReminder extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'invoice_id', 'message', 'is_automatically'
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function invoiceReminderDetails()
    {
        return $this->hasMany(InvoiceReminderDetail::class, 'invoice_reminder_id', 'id');
    }

    public function getSaveData()
    {
        $this->invoiceReminderDetails;
        $this->invoice;
        return $this;
    }
}