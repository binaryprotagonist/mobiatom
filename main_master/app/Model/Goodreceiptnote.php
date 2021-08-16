<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class Goodreceiptnote extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $table = 'good_receipt_note';

    protected $fillable = [
        'uuid', 'organisation_id', 'source_warehouse', 'destination_warehouse', 'grn_number', 'grn_date', 'grn_remark', 'status'
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

    public function sourceWarehouse()
    {
        // return $this->belongsTo(Warehouse::class,  'source_warehouse', 'id');
        return $this->belongsTo(Storagelocation::class,  'source_warehouse', 'id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Storagelocation::class,  'destination_warehouse', 'id');
        // return $this->belongsTo(Warehouse::class,  'destination_warehouse', 'id');
    }

    public function goodreceiptnotedetail()
    {
        return $this->hasMany(Goodreceiptnotedetail::class,  'good_receipt_note_id', 'id');
    }

    public function getSaveData()
    {
        $this->goodreceiptnotedetail;
        $this->sourceWarehouse;
        $this->destinationWarehouse;

        if (count($this->goodreceiptnotedetail)) {
            foreach ($this->goodreceiptnotedetail as $key => $detail) {
                $this->goodreceiptnotedetail[$key]->item = $detail->item;
                $this->goodreceiptnotedetail[$key]->itemUom = $detail->itemUom;
            }
        }

        return $this;
    }
}
