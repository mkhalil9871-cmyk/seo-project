<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankTracking extends Model
{
    protected $fillable = [
        'keyword_id',
        'domain',
        'position',
        'search_engine',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'date',
    ];

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }
}