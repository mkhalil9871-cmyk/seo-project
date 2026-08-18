<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPiece extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'keyword_id', 'title', 'body', 'status', 'generated_at'];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }
}