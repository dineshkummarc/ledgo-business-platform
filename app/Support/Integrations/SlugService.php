<?php

namespace App\Support\Integrations;

use ImranDev\UniversalSlug\SlugOptions;

interface SlugService
{
    public function businessOptions(): SlugOptions;
}
