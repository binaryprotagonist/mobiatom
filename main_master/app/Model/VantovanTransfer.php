<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class VantovanTransfer extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $table = 'van_to_van_transfer';

    protected $fillable = [
        'uuid', 'organisation_id', 'source_route_id', 'destination_route_id', 'code', 'date', 'status'
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
	
    public function vantovantransferdetail()
    {
        return $this->hasMany(VantovanTransferdetail::class,  'vantovantransfer_id', 'id');
    }
	
	public function sourceroute()
    {
        return $this->belongsTo(Route::class,  'source_route_id', 'id');
    }
	
	public function destinationroute()
    {
        return $this->belongsTo(Route::class,  'destination_route_id', 'id');
    }

    public function getSaveData()
    {
        $this->sourceroute;
        $this->destinationroute;
        if (count($this->vantovantransferdetail)) {
            foreach ($this->vantovantransferdetail as $key => $detail) {
                $this->vantovantransferdetail[$key]->item = $detail->item;
                $this->vantovantransferdetail[$key]->itemUom = $detail->itemUom;
            }
        }
        return $this;
    }
}
