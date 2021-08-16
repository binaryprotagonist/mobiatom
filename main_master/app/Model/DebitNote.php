<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Invoice;
use App\User;
use App\Model\DebitNoteDetail;

class DebitNote extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'invoice_id', 'customer_id', 'salesman_id', 'debit_note_number', 'debit_note_date', 'payment_term', 'total_qty', 'total_discount_amount', 'total_net', 'total_vat', 'total_excise', 'grand_total', 'debit_note_comment', 'reason', 'lob_id', 'approval_status'
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
        return $this->belongsTo(Invoice::class,  'invoice_id', 'id');
    }
    
    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function debitNoteDetails()
    {
        return $this->hasMany(DebitNoteDetail::class,  'debit_note_id', 'id');
    }

    public function debitNoteListingfeeShelfrentRebatediscountDetails()
    {
        return $this->hasMany(DebitNoteListingfeeShelfrentRebatediscountDetail::class,  'debit_note_id', 'id');
    }

    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    }
    
    public function getSaveData()
    {
        $this->invoice;
        /* if ($this->invoice->item) {
            $this->invoice->item;
        } */
        /* if ($this->invoice->itemUom) {
            $this->invoice->itemUom;
        } */
        $this->customer;
        $this->salesman;
        $this->debitNoteDetails;
        $this->debitNoteListingfeeShelfrentRebatediscountDetails;
        $this->lob;
        return $this;
    }
}
