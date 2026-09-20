<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BendaharaNote extends Model
{
    use HasFactory, SoftDeletes;

    public const TIPE_BOSP = 'bosp';

    public const TIPE_BPOPP = 'bpopp';

    public const COLOR_YELLOW = 'yellow';

    public const COLOR_BLUE = 'blue';

    public const COLOR_GREEN = 'green';

    public const COLOR_RED = 'red';

    public const COLOR_PURPLE = 'purple';

    public const COLOR_GRAY = 'gray';

    protected $fillable = [
        'user_id',
        'tipe',
        'title',
        'content',
        'color',
        'is_pinned',
        'is_completed',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
