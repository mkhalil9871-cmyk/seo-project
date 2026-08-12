<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    const STATUS_QUEUED = 'queued';
    const STATUS_CRAWLING = 'crawling';
    const STATUS_SCORING = 'scoring';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'project_id', 'status', 'pages_crawled', 'pages_queued', 'pages_failed',
        'sitemap_urls_found', 'sitemap_urls_uncrawled',
        'overall_score', 'technical_score', 'content_score', 'comparison_json',
        'error_message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'comparison_json' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(UrlQueueItem::class, 'audit_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(CrawledPage::class, 'audit_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(PageIssue::class, 'audit_id');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED], true);
    }

    /** The audit run immediately before this one for the same project, for trend comparison. */
    public function previousAudit(): ?Audit
    {
        return static::where('project_id', $this->project_id)
            ->where('status', self::STATUS_COMPLETED)
            ->where('id', '<', $this->id)
            ->latest('id')
            ->first();
    }
}
