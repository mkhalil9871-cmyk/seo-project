<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerpResult extends Model
{
    use HasFactory;

    protected $fillable = ['keyword_id', 'position', 'url', 'title', 'raw', 'checked_at'];

    protected $casts = [
        'raw' => 'array',
        'checked_at' => 'datetime',
    ];

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }
}