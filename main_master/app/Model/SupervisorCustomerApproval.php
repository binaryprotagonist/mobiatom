<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SupervisorCustomerApproval extends Model
{
    protected $fillable = [
        'uuid', 'organisation_id', 'salesman_id', 'customer_id', 'supervisor_id', 'status'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    /**
     * Get the user that owns the SupervisorCustomerApproval
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    /**
     * Get the user that owns the SupervisorCustomerApproval
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id', 'id');
    }

    /**
     * Get the user that owns the SupervisorCustomerApproval
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'id');
    }
}
