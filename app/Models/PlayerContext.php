<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlayerContext Model (Stage 2)
 * 
 * Stores player context data for advisor personalization.
 * Designed for external ChatGPT deployment, not real-time session tracking.
 */
class PlayerContext extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'background_story',
        'industry',
        'business_type',
        'current_challenges',
        'goals',
        'communication_style',
        'detail_level',
        'example_preference',
        'framework_preferences',
        'last_advisor_export_at',
        'exported_advisors_count',
        'feedback_notes'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'current_challenges' => 'array',
        'goals' => 'array',
        'framework_preferences' => 'array',
        'last_advisor_export_at' => 'datetime',
        'exported_advisors_count' => 'integer'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * Communication style options
     */
    const COMMUNICATION_STYLES = [
        'direct' => 'Direct and to-the-point',
        'collaborative' => 'Collaborative and exploratory',
        'analytical' => 'Analytical and data-driven',
        'inspirational' => 'Inspirational and visionary'
    ];

    /**
     * Detail level options
     */
    const DETAIL_LEVELS = [
        'high' => 'Comprehensive with full context',
        'medium' => 'Balanced detail and brevity',
        'low' => 'Concise key points only'
    ];

    /**
     * Example preference options
     */
    const EXAMPLE_PREFERENCES = [
        'industry_specific' => 'Examples from my industry only',
        'general' => 'Broad cross-industry examples',
        'mixed' => 'Mix of industry-specific and general'
    ];

    /**
     * Get the user that owns the player context.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the player context has been used for exports
     */
    public function hasExports(): bool
    {
        return $this->exported_advisors_count > 0;
    }

    /**
     * Get a summary of the player context for display
     */
    public function getSummary(): string
    {
        $summary = [];
        
        if ($this->industry) {
            $summary[] = "Industry: {$this->industry}";
        }
        
        if ($this->business_type) {
            $summary[] = "Business: {$this->business_type}";
        }
        
        if ($this->current_challenges && count($this->current_challenges) > 0) {
            $challengeCount = count($this->current_challenges);
            $summary[] = "{$challengeCount} current challenge" . ($challengeCount > 1 ? 's' : '');
        }
        
        if ($this->goals && count($this->goals) > 0) {
            $goalCount = count($this->goals);
            $summary[] = "{$goalCount} goal" . ($goalCount > 1 ? 's' : '');
        }
        
        return implode(' | ', $summary) ?: 'No context configured';
    }

    /**
     * Get formatted communication style
     */
    public function getCommunicationStyleLabel(): string
    {
        return self::COMMUNICATION_STYLES[$this->communication_style] ?? 'Not set';
    }

    /**
     * Get formatted detail level
     */
    public function getDetailLevelLabel(): string
    {
        return self::DETAIL_LEVELS[$this->detail_level] ?? 'Not set';
    }

    /**
     * Get formatted example preference
     */
    public function getExamplePreferenceLabel(): string
    {
        return self::EXAMPLE_PREFERENCES[$this->example_preference] ?? 'Not set';
    }

    /**
     * Scope to get contexts with recent exports
     */
    public function scopeWithRecentExports($query, $days = 30)
    {
        return $query->where('last_advisor_export_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get active contexts (those with exports)
     */
    public function scopeActive($query)
    {
        return $query->where('exported_advisors_count', '>', 0);
    }
}