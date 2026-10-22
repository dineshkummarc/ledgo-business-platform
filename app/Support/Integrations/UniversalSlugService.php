<?php

namespace App\Support\Integrations;

use ImranDev\UniversalSlug\SlugOptions;

class UniversalSlugService implements SlugService
{
    public function businessOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->usingMode('preserve_unicode');
    }
}
