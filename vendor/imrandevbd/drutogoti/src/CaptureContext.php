<?php

declare(strict_types=1);

namespace Drutogoti;

use Illuminate\Support\Str;

class CaptureContext
{
    public string $requestId;
    
    /** @var array<int, array<string, mixed>> */
    public array $queries = [];
    
    /** @var array<int, array<string, mixed>> */
    public array $cacheEvents = [];
    
    /** @var array<int, array<string, mixed>> */
    public array $cookieEvents = [];
    
    public ?string $method = null;
    public ?string $path = null;
    public ?int $statusCode = null;
    public float $startTime;
    
    public function __construct()
    {
        $this->requestId = Str::uuid()->toString();
        $this->startTime = microtime(true);
    }
    
    /**
     * @param array<string, mixed> $data
     */
    public function addQuery(array $data): void
    {
        $this->queries[] = $data;
    }
    
    /**
     * @param array<string, mixed> $data
     */
    public function addCacheEvent(array $data): void
    {
        $this->cacheEvents[] = $data;
    }
    
    /**
     * @param array<string, mixed> $data
     */
    public function addCookieEvent(array $data): void
    {
        $this->cookieEvents[] = $data;
    }

    public function flushAndDispatch(): void
    {
        $duration = (microtime(true) - $this->startTime) * 1000;
        $memory = memory_get_peak_usage(true) / 1024 / 1024;
        
        $requestData = [
            'request_id' => $this->requestId,
            'method' => $this->method ?: (request()->method() ?? 'CLI'),
            'path' => $this->path ?: (request()->path() ?? 'cli'),
            'status_code' => $this->statusCode ?: (http_response_code() ?: 200),
            'total_duration_ms' => $duration,
            'memory_peak_mb' => $memory,
            'query_count' => count($this->queries),
            'cache_hit_count' => count(array_filter($this->cacheEvents, fn($e) => $e['event'] === 'hit')),
            'cache_miss_count' => count(array_filter($this->cacheEvents, fn($e) => $e['event'] === 'miss')),
        ];
        
        $payload = [
            'request' => $requestData,
            'queries' => $this->queries,
            'cache_events' => $this->cacheEvents,
            'cookie_events' => $this->cookieEvents,
        ];
        
        /** @var string|null $driver */
        $driver = config('drutogoti.storage.driver');
        
        if ($driver === 'queue') {
            /** @var string|null $connection */
            $connection = config('drutogoti.storage.queue_connection');
            /** @var string|null $queue */
            $queue = config('drutogoti.storage.queue_name');
            
            dispatch(new \Drutogoti\Jobs\StoreCapturedEvents($payload))
                ->onConnection($connection)
                ->onQueue($queue);
        } else {
            dispatch_sync(new \Drutogoti\Jobs\StoreCapturedEvents($payload));
        }
    }
}
