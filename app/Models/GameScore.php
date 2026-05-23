<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameScore extends Model
{
    protected $fillable = [
        'user_id',
        'game_session_id',
        'game_slug',
        'score',
        'level',
        'duration_seconds',
        'accuracy',
        'metadata',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'level' => 'integer',
            'duration_seconds' => 'integer',
            'accuracy' => 'integer',
            'metadata' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
