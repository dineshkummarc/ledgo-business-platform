<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['user_id', 'event', 'subject_type', 'subject_id', 'old_values', 'new_values', 'ip', 'device'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }
}
