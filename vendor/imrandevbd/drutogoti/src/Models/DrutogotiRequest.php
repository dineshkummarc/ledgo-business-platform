<?php

declare(strict_types=1);

namespace Drutogoti\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrutogotiRequest extends Model
{
    /** @var array<string> */
    protected $guarded = ['id'];
    
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable('drutogoti_requests');
    }

    /**
     * @return HasMany<QueryLog, $this>
     */
    public function queries(): HasMany
    {
        return $this->hasMany(QueryLog::class, 'request_id', 'request_id');
    }
}
