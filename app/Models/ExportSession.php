<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category_key', 'category_name', 'material', 'status', 'questions', 'answers', 'result', 'engine', 'submitted_at', 'guest_token'])]
class ExportSession extends Model
{
    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'answers' => 'array',
            'result' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Started by a guest and not yet claimed by logging in/registering.
     */
    public function isGuestOwned(): bool
    {
        return $this->user_id === null;
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * NOTE: despite the name, this does NOT mean "compliant" or "cleared for
     * export". It means BackendAiComplianceEngine found a candidate HS code
     * from the answers given — 'fail' means the answers didn't match its
     * classification branch and need manual review instead. Kept as
     * pass/fail so dashboard stats and history badges don't need their own
     * vocabulary; see BackendAiComplianceEngine::evaluate()'s doc comment.
     */
    public function passed(): bool
    {
        return ($this->result['verdict'] ?? null) === 'pass';
    }
}
