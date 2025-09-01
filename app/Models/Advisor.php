<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advisor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'name',
        'full_name',
        'known_for',
        'era',
        'style',
        'industry',
        'primary_objective',
        'core_expertise_area',
        'related_expertise_areas',
        'communication_style_description',
        'decision_making_approach',
        'key_phrases_or_terminology',
        'emotional_characteristics',
        'unique_perspectives_or_contrarian_stances',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'related_expertise_areas' => 'array',
        'key_phrases_or_terminology' => 'array',
    ];

    /**
     * Scope to find advisor by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get the advisor configuration as an array.
     */
    public function getConfigArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'known_for' => $this->known_for,
            'era' => $this->era,
            'style' => $this->style,
            'industry' => $this->industry,
            'primary_objective' => $this->primary_objective,
            'core_expertise_area' => $this->core_expertise_area,
            'related_expertise_areas' => is_array($this->related_expertise_areas) 
                ? implode(', ', $this->related_expertise_areas) 
                : $this->related_expertise_areas,
            'communication_style_description' => $this->communication_style_description,
            'decision_making_approach' => $this->decision_making_approach,
            'key_phrases_or_terminology' => is_array($this->key_phrases_or_terminology)
                ? implode(', ', $this->key_phrases_or_terminology)
                : $this->key_phrases_or_terminology,
            'emotional_characteristics' => $this->emotional_characteristics,
            'unique_perspectives_or_contrarian_stances' => $this->unique_perspectives_or_contrarian_stances,
        ];
    }
}