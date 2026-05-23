<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['session_id', 'ip', 'country', 'url', 'referer', 'user_agent_hash', 'visited_at'])]
class Visit extends Model
{
    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
