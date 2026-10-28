<?php

declare(strict_types=1);

namespace Drutogoti\Watchers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Drutogoti\CaptureContext;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class QueryWatcher
{
    public function __construct(protected CaptureContext $context)
    {
    }

    public function register(): void
    {
        /** @var bool $enabled */
        $enabled = config('drutogoti.watchers.queries.enabled', true);
        if (!$enabled) {
            return;
        }

        DB::listen(function (QueryExecuted $query) {
            $this->handleQuery($query);
        });
    }

    protected function handleQuery(QueryExecuted $query): void
    {
        if (Str::contains($query->sql, 'drutogoti_')) {
            return;
        }

        $caller = $this->getCaller();
        
        /** @var array<int, string> $excluded */
        $excluded = config('drutogoti.watchers.queries.exclude_paths', []);
        foreach ($excluded as $path) {
            if ($caller && Str::contains($caller, $path)) {
                return;
            }
        }

        /** @var \Illuminate\Routing\Route|null $route */
        $route = Route::current();

        $this->context->addQuery([
            'request_id' => $this->context->requestId,
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'duration_ms' => $query->time,
            'connection' => $query->connectionName,
            'route' => $route ? $route->uri() : null,
            'controller_action' => $caller,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function getCaller(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        foreach ($trace as $frame) {
            if (isset($frame['file']) && !Str::contains($frame['file'], 'vendor')) {
                $class = $frame['class'] ?? '';
                $function = $frame['function'];
                return $class ? "{$class}@{$function}" : ($frame['file'] . ':' . ($frame['line'] ?? '0'));
            }
        }

        return null;
    }
}
