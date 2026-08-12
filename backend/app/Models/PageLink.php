<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLink extends Model
{
    protected $fillable = [
        'audit_id', 'source_url', 'target_url', 'target_url_hash',
        'anchor_text', 'is_internal', 'is_nofollow',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_nofollow' => 'boolean',
    ];
}
