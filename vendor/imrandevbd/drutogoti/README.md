<p align="center">
  <h1 align="center">⚡ Drutogoti (দ্রুতগতি) for Laravel</h1>
  <p align="center">
    <strong>Auto-detect, visualize, and fix performance bottlenecks in Laravel applications.</strong>
  </p>
  <p align="center">
    <a href="https://packagist.org/packages/imrandevbd/drutogoti"><img src="https://img.shields.io/packagist/v/imrandevbd/drutogoti.svg?style=flat-square&color=F9322C" alt="Latest Version on Packagist"></a>
    <a href="https://packagist.org/packages/imrandevbd/drutogoti"><img src="https://img.shields.io/packagist/dt/imrandevbd/drutogoti.svg?style=flat-square&color=blue" alt="Total Downloads"></a>
    <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-%5E8.3-777BB4.svg?style=flat-square&logo=php&logoColor=white" alt="PHP Version"></a>
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11.x%20%7C%2012.x-FF2D20.svg?style=flat-square&logo=laravel&logoColor=white" alt="Laravel Version"></a>
    <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-green.svg?style=flat-square" alt="License"></a>
    <a href="https://github.com/imranbru99/laravel-speed-optimization-drutogoti/stargazers"><img src="https://img.shields.io/github/stars/imranbru99/laravel-speed-optimization-drutogoti?style=flat-square&color=yellow" alt="GitHub Stars"></a>
  </p>
</p>

---

## 📖 Introduction

**Drutogoti** (*Bengali for "Rapid Velocity / High Speed"*) is a lightweight, zero-overhead Application Performance Profiler and Optimization Engine designed specifically for **Laravel 11.x and 12.x**.

Modern web applications slow down silently. Hidden N+1 queries, unindexed slow queries, repeated cache misses, oversized header cookies, and unoptimized image payloads degrade user experience and inflate cloud server costs. 

Drutogoti runs in the background of your application, intercepts key lifecycle events, and offloads analysis asynchronously to your queue worker—guaranteeing **virtually zero response-time overhead** for production workloads.

---

## 🚀 Key Highlights

* 🔍 **Autonomous N+1 Query Detection:** Groups duplicate and recurring SQL statements across request cycles and flags relationships that require eager loading.
* ⏱️ **Slow Query Diagnostics:** Intercepts database queries exceeding configurable execution latency thresholds (e.g., > 100ms) with full backtrace down to the calling controller line.
* ⚡ **Non-Blocking Queue Offloading:** Event telemetry is buffered in memory during request lifecycle and dispatched in the `terminating` callback to background queue workers (`sync` or `queue` drivers).
* 🗄️ **Cache Performance Telemetry:** Real-time metrics on cache hits and misses to detect cold caches or missing cache strategies.
* 🍪 **Cookie & Header Bloat Monitoring:** Monitors cookie payloads and triggers alerts when request/response cookies breach maximum sizing budgets (default: 4096 bytes).
* 🖼️ **Asset & Image Scanner:** Detects uncompressed image assets across public directories and recommends compression workflows using standard CLI binaries (`jpegoptim`, `optipng`, `pngquant`, `cwebp`).
* 💡 **Intelligent Suggestion Engine:** Deduplicates repetitive issues and tracks first-seen, last-seen, and frequency occurrences with severity scoring.
* 🛡️ **Production-Safe Sampling & Route Filtering:** Configurable sampling rate (`DRUTOGOTI_SAMPLE_RATE=0.1` for 10% sampling) and path exclusion patterns (`/up`, `/telescope*`, `/horizon*`).

---

## 📦 Requirements

* **PHP:** `^8.3`
* **Laravel:** `^11.0` or `^12.0`
* **Database:** MySQL, PostgreSQL, SQLite, or MariaDB

---

## ⚡ Installation

Install Drutogoti into your project via Composer:

```bash
composer require imrandevbd/drutogoti
```

### 1. Publish Configuration & Migrations

Publish the package configuration file and database migrations:

```bash
php artisan vendor:publish --tag="drutogoti-config"
php artisan vendor:publish --tag="drutogoti-migrations"
```

### 2. Run Database Migrations

Run the migrations to create the required telemetry and suggestion tables:

```bash
php artisan migrate
```

---

## ⚙️ Configuration

The configuration file is published to `config/drutogoti.php`. You can customize settings directly or override them via your `.env` file:

```env
# Enable or disable Drutogoti profiling
DRUTOGOTI_ENABLED=true

# Storage Mode: 'queue' (recommended for production) or 'sync' (development)
DRUTOGOTI_STORAGE_DRIVER=queue
DRUTOGOTI_QUEUE_CONNECTION=redis
DRUTOGOTI_QUEUE_NAME=default

# Query Watcher Thresholds
DRUTOGOTI_WATCHER_QUERIES_ENABLED=true
DRUTOGOTI_SLOW_THRESHOLD_MS=100
DRUTOGOTI_N_PLUS_ONE_THRESHOLD=3

# Cache & Cookie Watchers
DRUTOGOTI_WATCHER_CACHE_ENABLED=true
DRUTOGOTI_WATCHER_COOKIES_ENABLED=true

# Image Scanner
DRUTOGOTI_WATCHER_IMAGES_ENABLED=true

# Production Request Sampling (1.0 = 100%, 0.1 = 10%)
DRUTOGOTI_SAMPLE_RATE=1.0
```

### Configuration Overview

```php
return [
    'enabled' => env('DRUTOGOTI_ENABLED', false),

    'storage' => [
        // 'queue' offloads event writing to avoid blocking user HTTP responses
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
            'exclude_paths' => [
                'vendor',
            ],
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
                'optipng'   => '/usr/bin/optipng',
                'pngquant'  => '/usr/bin/pngquant',
                'gifsicle'  => '/usr/bin/gifsicle',
                'cwebp'     => '/usr/bin/cwebp',
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
        'up', // Laravel health check
    ],

    'sample_rate' => env('DRUTOGOTI_SAMPLE_RATE', 1.0),
];
```

---

## 🛠️ How It Works

### Zero-Overhead Lifecycle Hook

Drutogoti hooks into Laravel's request lifecycle using a singleton `CaptureContext`:

```
Incoming Request
      │
      ▼
┌────────────────────────────────────────┐
│ App Execution & Query Listeners       │
│ (In-memory capture of SQL, Cache, etc.)│
└────────────────────────────────────────┘
      │
      ▼
Response Sent to Client (HTTP 200)
      │
      ▼
┌────────────────────────────────────────┐
│ App Terminating Hook                   │
│ └── flushAndDispatch()                 │
│     └── Dispatched to Queue Worker     │
└────────────────────────────────────────┘
      │
      ▼
Async StoreCapturedEvents Job -> Database Log & Suggestion Engine
```

Because telemetry persistence is deferred to queue workers after HTTP responses are flushed to the browser, **users experience no added latency**.

---

## 🧪 Testing

Drutogoti is thoroughly tested using **Pest PHP** and **PHPStan**:

```bash
# Run Pest test suite
composer test

# Run static analysis
composer analyse

# Run code style formatting
composer format
```

---

## 🗺️ Roadmap

- [x] Query & N+1 Watcher with execution backtracing
- [x] Asynchronous queue ingestion
- [x] Intelligent suggestion grouping and deduplication
- [ ] Dedicated UI Dashboard for live telemetry visualization
- [ ] Slack & Discord webhook alerting for critical performance drops
- [ ] GitHub PR automated query regression check integration

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](https://github.com/imranbru99/laravel-speed-optimization-drutogoti/issues) if you want to contribute.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This open-source software is licensed under the [MIT License](LICENSE).

---

## 🤝 Let's Connect & Collaborate

I am open to **Senior Remote Full-Stack Roles**, **AI Platform Architecture Contracts**, and **Enterprise Technical Advisory**.

* **Timezone:** UTC+6 (Dhaka / Rangpur, Bangladesh) — Flexible overlap with US, UK, and European business hours.
* **Delivery Mode:** Async-ready, Slack, Discord, Jira, GitHub, and production-first accountability.

| Channel | Address / Handle | Quick Action |
| :--- | :--- | :--- |
| 🌐 **Primary Portfolio** | [imrandev.bd](https://imrandev.bd/) | [Visit Site ↗](https://imrandev.bd/) |
| 📦 **Packagist Packages** | [packagist.org/packages/imrandevbd/](https://packagist.org/packages/imrandevbd/) | [View Packages ↗](https://packagist.org/packages/imrandevbd/) |
| 💼 **LinkedIn Profile** | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) | [Connect ↗](https://linkedin.com/in/imranbru99) |
| 🐙 **GitHub Profile** | [github.com/imranbru99](https://github.com/imranbru99) | [Follow ↗](https://github.com/imranbru99) |
| 💬 **WhatsApp Direct** | [+880 1576-918420](http://wa.me/+8801576918420) | [Chat Now ↗](http://wa.me/+8801576918420) |
| 📧 **Direct Email** | [me@imrandev.bd](mailto:me@imrandev.bd) | [Send Email ↗](mailto:me@imrandev.bd) |
| 🐦 **X (Twitter)** | [@imrandev_bd](https://x.com/imrandev_bd) | [Follow ↗](https://x.com/imrandev_bd) |
| 📺 **YouTube Tech** | [@ImranDevBD](https://youtube.com/@ImranDevBD) | [Subscribe ↗](https://youtube.com/@ImranDevBD) |
