<?php

namespace App\Support\Tenancy;

use App\Models\Business;
use Closure;

final class BusinessContext
{
    private ?Business $business = null;

    public function current(): ?Business
    {
        return $this->business;
    }

    public function run(Business $business, Closure $callback): mixed
    {
        $previous = $this->business;
        $this->business = $business;
        try {
            return $callback();
        } finally {
            $this->business = $previous;
        }
    }
}
