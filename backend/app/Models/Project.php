<?php 
 
namespace App\Models; 
 
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
use Illuminate\Database\Eloquent\Relations\HasMany; 
 
class Project extends Model 
{ 
    use HasFactory; 
 
    protected $fillable = [ 
        'user_id', 'name', 'domain', 'industry', 'country', 'language', 'status', 
        'sitemap_url', 'max_pages', 'max_depth', 'crawl_delay_ms', 'respect_robots', 
    ]; 
 
    protected $casts = [ 
        'respect_robots' => 'boolean', 
    ]; 
 
    public function user(): BelongsTo 
    { 
        return $this->belongsTo(User::class); 
    } 
 
    /** 
     * `domain` is stored as entered by the user (often bare, e.g. "example.com", 
     * sometimes a full URL). The crawler needs a proper scheme to resolve relative 
     * links and compare hosts, so this accessor normalizes it once, in one place, 
     * instead of every service having to guess. 
     */ 
    public function getBaseUrlAttribute(): string 
    { 
        $domain = trim($this->attributes['domain'] ?? ''); 
 
        if (preg_match('#^https?://#i', $domain)) { 
            return rtrim($domain, '/'); 
        } 
 
        return 'https://' . rtrim($domain, '/'); 
    } 
 
    public function audits(): HasMany 
    { 
        return $this->hasMany(Audit::class); 
    } 

    public function contentPieces(): HasMany
{
    return $this->hasMany(ContentPiece::class);
}
 
    public function keywords(): HasMany 
    { 
        return $this->hasMany(Keyword::class); 
    } 
 
    public function latestAudit() 
    { 
        return $this->audits()->latest()->first(); 
    } 
 
    public function latestCompletedAudit() 
    { 
        return $this->audits()->where('status', Audit::STATUS_COMPLETED)->latest()->first(); 
    } 
} 