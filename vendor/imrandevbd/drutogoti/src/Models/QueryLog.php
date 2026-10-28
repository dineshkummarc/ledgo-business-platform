<?php

declare(strict_types=1);

namespace Drutogoti\Models;

use Illuminate\Database\Eloquent\Model;

class QueryLog extends Model
{
    /** @var array<string> */
    protected $guarded = ['id'];

    /** @var array<string, string> */
    protected $casts = [
        'bindings' => 'json',
        'is_duplicate' => 'boolean',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable('drutogoti_query_logs');
    }
}
