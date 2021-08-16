<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;


class Verification extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'token', 'email', 'code'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}