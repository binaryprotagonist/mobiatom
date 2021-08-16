<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Invoice;
use App\Model\Lob;
use App\User;


class Collection extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'invoice_id', 'customer_id', 'oddo_collection_id', 'odoo_failed_response', 'salesman_id', 'route_id', 'collection_number', 'payemnt_type', 'invoice_amount', 'discount', 'collection_status', 'cheque_number', 'cheque_date', 'bank_info', 'transaction_number', 'current_stage', 'current_stage_comment', 'oddo_collection_id', 'odoo_failed_response', 'lob_id', 'status', 'source'
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

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function collectiondetails()
    {
        return $this->hasMany(CollectionDetails::class,  'collection_id', 'id');
    }
    public function lob()
    {
        return $this->belongsTo(Lob::class, 'lob_id', 'id');
    }

    public function getSaveData()
    {
        $this->invoice;
        $this->customer;
        $this->salesman;
        $this->collectiondetails;
        $this->lob;
        $this->route;
        return $this;
    }

    public function getSaveOddoData()
    {
        $this->invoice;
        $this->customer;
        $this->salesman;
        $this->collectiondetails;
        $this->lob;
        $this->route;

        // if (count($this->collectiondetails)) {
        //     foreach ($this->collectiondetails as $key => $collections) {
        //         $this->invoices[$key]->customer = $collections->customer;
        //         $this->invoices[$key]->lob = $collections->lob;
        //         if ($this->invoices[$key]->type === 1) {
        //             $this->invoices[$key]->invoice = $collections->invoice;
        //         }
        //         if ($this->invoices[$key]->type === 2) {
        //             $this->invoices[$key]->debit_note = $collections->debit_note;
        //         }
        //         if ($this->invoices[$key]->type === 3) {
        //             $this->invoices[$key]->credit_note = $collections->credit_note;
        //         }
        //     }
        // }

        return $this;
    }
}
