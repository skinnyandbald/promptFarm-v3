<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvisorPosition extends Model
{
    protected $fillable = [
        'advisor_slug',
        'researched_positions',
        'research_model',
        'research_temperature',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'research_temperature' => 'float',
    ];

    /**
     * Get the advisor that this position belongs to
     */
    public function advisor()
    {
        return $this->belongsTo(Advisor::class, 'advisor_slug', 'slug');
    }
}
