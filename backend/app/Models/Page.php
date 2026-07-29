<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'url', 'title', 'h1', 'status_code'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }
}