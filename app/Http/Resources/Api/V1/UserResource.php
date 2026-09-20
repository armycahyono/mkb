<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->role_label ?? ucfirst(str_replace('_', ' ', $this->role ?? '')),
            'guru_id' => $this->guru_id,
            'guru_name' => $this->whenLoaded('guru', fn () => $this->guru->name),
            'telegram_chat_id' => $this->telegram_chat_id,
            'has_telegram' => ! empty($this->telegram_chat_id),
        ];
    }
}
