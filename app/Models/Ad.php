<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'poster',
        'duration',
        'points_reward',
        'is_active',
    ];

    /**
     * Ensure numeric fields are cast to integers.
     */
    protected $casts = [
        'duration' => 'integer',
        'points_reward' => 'integer',
        'is_active' => 'boolean',
    ];

    public function adViews()
    {
        return $this->hasMany(AdView::class);
    }
}
