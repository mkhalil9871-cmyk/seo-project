<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'keyword', 'cluster', 'intent', 'serp_status', 'last_checked_at'];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function serpResults()
    {
        return $this->hasMany(SerpResult::class);
    }

    public function latestResult()
    {
        return $this->hasOne(SerpResult::class)->latestOfMany('checked_at');
    }
}