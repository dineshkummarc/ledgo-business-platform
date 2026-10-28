<?php

declare(strict_types=1);

namespace Drutogoti\Models;

use Illuminate\Database\Eloquent\Model;

class ImageScan extends Model
{
    /** @var array<string> */
    protected $guarded = ['id'];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable('drutogoti_image_scans');
    }
}
