<?php

declare(strict_types=1);

namespace Drutogoti\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Drutogoti\Models\DrutogotiRequest;
use Drutogoti\Models\QueryLog;
use Drutogoti\Models\CacheEvent;
use Drutogoti\Models\CookieEvent;
use Drutogoti\Services\SuggestionEngine;
use Illuminate\Support\Str;

class StoreCapturedEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public array $payload)
    {
    }

    public function handle(SuggestionEngine $suggestionEngine): void
    {
        /** @var array<string, mixed> $requestData */
        $requestData = $this->payload['request'];
        DrutogotiRequest::create($requestData);

        /** @var array<int, array<string, mixed>> $queries */
        $queries = $this->payload['queries'];
        
        // Group identical queries for N+1 detection
        $queryGroups = [];
        foreach ($queries as $query) {
            /** @var string $sql */
            $sql = $query['sql'];
            if (!isset($queryGroups[$sql])) {
                $queryGroups[$sql] = 0;
            }
            $queryGroups[$sql]++;
        }
        
        /** @var int $nPlusOneThreshold */
        $nPlusOneThreshold = config('drutogoti.watchers.queries.n_plus_one_threshold', 3);

        foreach ($queries as $query) {
            /** @var string $sql */
            $sql = $query['sql'];
            
            if ($queryGroups[$sql] > $nPlusOneThreshold) {
                $query['n_plus_one_group_id'] = md5($requestData['request_id'] . $sql);
                $suggestionEngine->analyzeQueryForNPlusOne($query, $queryGroups[$sql]);
            }
            
            $suggestionEngine->analyzeQueryForSlowness($query);
            
            QueryLog::create($query);
        }

        /** @var array<int, array<string, mixed>> $cacheEvents */
        $cacheEvents = $this->payload['cache_events'] ?? [];
        foreach ($cacheEvents as $event) {
            CacheEvent::create($event);
        }

        /** @var array<int, array<string, mixed>> $cookieEvents */
        $cookieEvents = $this->payload['cookie_events'] ?? [];
        foreach ($cookieEvents as $event) {
            CookieEvent::create($event);
        }
    }
}
