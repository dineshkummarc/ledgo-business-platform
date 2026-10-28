<?php

declare(strict_types=1);

namespace Drutogoti\Services;

use Drutogoti\Models\Suggestion;

class SuggestionEngine
{
    /**
     * @param array<string, mixed> $query
     */
    public function analyzeQueryForNPlusOne(array $query, int $count): void
    {
        /** @var string $sql */
        $sql = $query['sql'];
        $title = 'Potential N+1 Query Detected';
        $description = "The query `{$sql}` was executed {$count} times. You should eager load this relationship.";
        
        $this->logSuggestion('query', 'critical', $title, $description, $query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function analyzeQueryForSlowness(array $query): void
    {
        /** @var float $duration */
        $duration = $query['duration_ms'];
        /** @var int|float $threshold */
        $threshold = config('drutogoti.watchers.queries.slow_threshold_ms', 100);
        
        if ($duration > (float) $threshold) {
            /** @var string $sql */
            $sql = $query['sql'];
            $title = 'Slow Query Detected';
            $description = "The query `{$sql}` took {$duration}ms to execute.";
            
            $this->logSuggestion('query', 'warning', $title, $description, $query);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function logSuggestion(string $category, string $severity, string $title, string $description, array $context): void
    {
        /** @var string $requestId */
        $requestId = $context['request_id'] ?? null;
        
        $suggestion = Suggestion::where('title', $title)
            ->where('category', $category)
            ->first();

        if ($suggestion) {
            $suggestion->increment('occurrences_count');
            $suggestion->update(['last_seen_at' => now(), 'related_request_id' => $requestId]);
        } else {
            Suggestion::create([
                'category' => $category,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'related_request_id' => $requestId,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'occurrences_count' => 1,
            ]);
        }
    }
}
