<?php

namespace App\Support\Integrations;

interface PerformanceProfiler
{
    public function enabled(): bool;

    public function slowThreshold(): int;
}
