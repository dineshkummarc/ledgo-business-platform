<?php

return [
    'enabled' => env('DRUTOGOTI_ENABLED', false),

    'storage' => [
        // Mode for storing events: 'sync' or 'queue'
        // 'queue' is highly recommended for production to avoid blocking requests
        'driver' => env('DRUTOGOTI_STORAGE_DRIVER', 'queue'),
        'queue_connection' => env('DRUTOGOTI_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'queue_name' => env('DRUTOGOTI_QUEUE_NAME', 'default'),
        'retention_days' => 7,
        'auto_prune' => true,
    ],

    'watchers' => [
        'queries' => [
            'enabled' => env('DRUTOGOTI_WATCHER_QUERIES_ENABLED', true),
            'slow_threshold_ms' => 100,
            'n_plus_one_threshold' => 3,
            'exclude_paths' => [],
        ],
        'cache' => [
            'enabled' => env('DRUTOGOTI_WATCHER_CACHE_ENABLED', true),
        ],
        'cookies' => [
            'enabled' => env('DRUTOGOTI_WATCHER_COOKIES_ENABLED', true),
            'size_warning_bytes' => 4096,
        ],
        'images' => [
            'enabled' => env('DRUTOGOTI_WATCHER_IMAGES_ENABLED', true),
            'scan_paths' => [
                'public',
                'storage/app/public',
            ],
            'binaries' => [
                'jpegoptim' => '/usr/bin/jpegoptim',
                'optipng' => '/usr/bin/optipng',
                'pngquant' => '/usr/bin/pngquant',
                'gifsicle' => '/usr/bin/gifsicle',
                'cwebp' => '/usr/bin/cwebp',
            ],
        ],
    ],

    'dashboard' => [
        'enabled' => true,
        'route_prefix' => 'drutogoti',
        'middleware' => ['web', 'auth'],
    ],
    
    'exclude_paths' => [
        'drutogoti*',
        'up', // Laravel 11 health check
    ],
    
    'sample_rate' => env('DRUTOGOTI_SAMPLE_RATE', 1.0), // 1.0 = log 100% of requests
];
