<?php

declare(strict_types=1);

namespace Drutogoti\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    /** @var array<string> */
    protected $guarded = ['id'];

    /** @var array<string, string> */
    protected $casts = [
        'is_resolved' => 'boolean',
        'is_dismissed' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable('drutogoti_suggestions');
    }
}
