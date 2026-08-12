<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageIssue extends Model
{
    // Severity levels, roughly matching how Semrush/Screaming Frog bucket issues
    const SEVERITY_CRITICAL = 'critical'; // breaks indexing/functionality (5xx, noindex on live page, broken canonical)
    const SEVERITY_HIGH = 'high';         // real ranking impact (missing title, duplicate content)
    const SEVERITY_MEDIUM = 'medium';     // best-practice gaps (short meta description)
    const SEVERITY_LOW = 'low';           // minor/cosmetic (missing alt on a decorative image)

    protected $fillable = ['audit_id', 'crawled_page_id', 'type', 'severity', 'category', 'detail'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CrawledPage::class, 'crawled_page_id');
    }
}
