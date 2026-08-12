<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawledPage extends Model
{
    protected $fillable = [
        'audit_id', 'url', 'url_hash', 'depth', 'discovered_via', 'status_code', 'redirect_to',
        'response_time_ms', 'html_size_bytes', 'content_type', 'x_robots_tag', 'title', 'title_hash',
        'meta_description', 'meta_description_hash',
        'meta_robots', 'canonical_url', 'canonical_status', 'charset', 'lang', 'has_viewport',
        'headings', 'word_count', 'content_hash', 'internal_link_count', 'external_link_count',
        'inbound_internal_links', 'image_count', 'images_missing_alt', 'json_ld', 'has_schema',
        'hreflangs', 'rel_next', 'rel_prev', 'is_https', 'has_mixed_content', 'is_indexable', 'error_message',
    ];

    protected $casts = [
        'headings' => 'array',
        'json_ld' => 'array',
        'hreflangs' => 'array',
        'has_viewport' => 'boolean',
        'has_schema' => 'boolean',
        'is_https' => 'boolean',
        'has_mixed_content' => 'boolean',
        'is_indexable' => 'boolean',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(PageIssue::class, 'crawled_page_id');
    }
}
