<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInventory extends Model
{
    protected $fillable = [
        'user_id',
        'item_type',
        'item_data',
        'quantity',
    ];

    protected $casts = [
        'item_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
