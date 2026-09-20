<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BendaharaTask extends Model
{
    use HasFactory, SoftDeletes;

    public const TIPE_BOSP = 'bosp';

    public const TIPE_BPOPP = 'bpopp';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_IMPORTANT = 'penting';

    public const PRIORITY_URGENT = 'mendesak';

    protected $fillable = [
        'user_id',
        'tipe',
        'title',
        'description',
        'due_date',
        'due_time',
        'priority',
        'kegiatan_ref',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('due_date', Carbon::today());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_completed', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('is_completed', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>', Carbon::today());
    }

    /**
     * Helper status jatuh tempo: overdue, today, upcoming, completed, none
     */
    public function getDueStatusAttribute(): string
    {
        if ($this->is_completed) {
            return 'completed';
        }

        if (! $this->due_date) {
            return 'none';
        }

        $today = Carbon::today();
        $due = Carbon::parse($this->due_date)->startOfDay();

        if ($due->lt($today)) {
            return 'overdue';
        }

        if ($due->equalTo($today)) {
            return 'today';
        }

        return 'upcoming';
    }

    /**
     * Label jatuh tempo ringkas untuk UI
     */
    public function getDueLabelAttribute(): ?string
    {
        if (! $this->due_date) {
            return null;
        }

        $today = Carbon::today();
        $due = Carbon::parse($this->due_date)->startOfDay();

        if ($this->is_completed) {
            return 'Selesai: '.($this->completed_at ? $this->completed_at->format('d M') : $due->format('d M'));
        }

        if ($due->equalTo($today)) {
            return 'Hari Ini'.($this->due_time ? ' '.substr($this->due_time, 0, 5) : '');
        }

        if ($due->equalTo($today->copy()->addDay())) {
            return 'Besok'.($this->due_time ? ' '.substr($this->due_time, 0, 5) : '');
        }

        if ($due->lt($today)) {
            $diffDays = (int) $due->diffInDays($today);

            return 'Terlambat '.($diffDays > 0 ? $diffDays.' hr' : '');
        }

        return $due->format('d M Y').($this->due_time ? ' '.substr($this->due_time, 0, 5) : '');
    }

    /**
     * Kelas badge status jatuh tempo
     */
    public function getDueBadgeClassAttribute(): string
    {
        return match ($this->due_status) {
            'overdue' => 'badge-danger',
            'today' => 'badge-warning text-dark font-weight-bold',
            'upcoming' => 'badge-info',
            'completed' => 'badge-secondary',
            default => 'badge-light text-muted',
        };
    }

    /**
     * Label prioritas
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'Mendesak',
            self::PRIORITY_IMPORTANT => 'Penting',
            default => 'Normal',
        };
    }

    /**
     * Kelas badge prioritas
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'badge-danger',
            self::PRIORITY_IMPORTANT => 'badge-warning text-dark',
            default => 'badge-light border text-muted',
        };
    }
}
