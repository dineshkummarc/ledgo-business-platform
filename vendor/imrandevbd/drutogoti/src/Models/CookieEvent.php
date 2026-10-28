<?php

declare(strict_types=1);

namespace Drutogoti\Models;

use Illuminate\Database\Eloquent\Model;

class CookieEvent extends Model
{
    /** @var array<string> */
    protected $guarded = ['id'];

    /** @var array<string, string> */
    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_http_only' => 'boolean',
        'is_secure' => 'boolean',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable('drutogoti_cookie_events');
    }
}
