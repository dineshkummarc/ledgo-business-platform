<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Drutogoti\CaptureContext;

it('captures queries and stores them', function () {
    config()->set('drutogoti.enabled', true);
    config()->set('drutogoti.watchers.queries.enabled', true);
    config()->set('drutogoti.storage.driver', 'sync');
    
    DB::statement('CREATE TABLE dummy (id INTEGER PRIMARY KEY)');
    
    $context = app(CaptureContext::class);
    $context->queries = [];
    
    DB::insert('INSERT INTO dummy (id) VALUES (?)', [1]);
    DB::select('SELECT * FROM dummy WHERE id = ?', [1]);
    
    $context->flushAndDispatch();
    
    $this->assertDatabaseHas('drutogoti_requests', [
        'request_id' => $context->requestId,
        'query_count' => 2,
    ]);
    
    $this->assertDatabaseHas('drutogoti_query_logs', [
        'request_id' => $context->requestId,
        'sql' => 'INSERT INTO dummy (id) VALUES (?)',
    ]);
});

it('detects n+1 queries', function () {
    config()->set('drutogoti.enabled', true);
    config()->set('drutogoti.watchers.queries.enabled', true);
    config()->set('drutogoti.storage.driver', 'sync');
    config()->set('drutogoti.watchers.queries.n_plus_one_threshold', 2);
    
    DB::statement('CREATE TABLE n_plus_one (id INTEGER PRIMARY KEY)');
    
    $context = app(CaptureContext::class);
    $context->queries = [];
    
    DB::select('SELECT * FROM n_plus_one WHERE id = ?', [1]);
    DB::select('SELECT * FROM n_plus_one WHERE id = ?', [2]);
    DB::select('SELECT * FROM n_plus_one WHERE id = ?', [3]);
    
    $context->flushAndDispatch();
    
    $this->assertDatabaseHas('drutogoti_suggestions', [
        'category' => 'query',
        'title' => 'Potential N+1 Query Detected',
    ]);
});

it('detects slow queries', function () {
    config()->set('drutogoti.enabled', true);
    config()->set('drutogoti.watchers.queries.enabled', true);
    config()->set('drutogoti.storage.driver', 'sync');
    config()->set('drutogoti.watchers.queries.slow_threshold_ms', -1);
    
    DB::statement('CREATE TABLE slow_queries (id INTEGER PRIMARY KEY)');
    
    $context = app(CaptureContext::class);
    $context->queries = [];
    
    DB::select('SELECT * FROM slow_queries');
    
    $context->flushAndDispatch();
    
    $this->assertDatabaseHas('drutogoti_suggestions', [
        'category' => 'query',
        'title' => 'Slow Query Detected',
    ]);
});
