<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdView extends Model
{
    protected $fillable = [
        'user_id',
        'ad_id',
        'started_at',
        'completed_at',
        'points_awarded',
    ];

    /**
     * Cast timestamps and flags to proper types so date math uses Carbon and booleans are correct.
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'points_awarded' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
