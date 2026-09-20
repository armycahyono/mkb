<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'user_id',
        'command',
        'step',
        'draft_data',
    ];

    protected $casts = [
        'draft_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
