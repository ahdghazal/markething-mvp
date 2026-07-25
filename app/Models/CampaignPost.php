<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'sequence_number',
        'scheduled_date',
        'channel',
        'media_type',
        'summary',
        'caption',
        'hashtags',
        'creative_direction',
        'is_edited',
        'is_regenerated',
        'regeneration_count',
        'boost_recommended'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'sequence_number' => 'integer',
        'is_edited' => 'boolean',
        'is_regenerated' => 'boolean',
        'regeneration_count' => 'integer',
        'boost_recommended' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function llmLogs(): HasMany
    {
        return $this->hasMany(LlmLog::class);
    }
}