<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAiEmbeddingSemanticSearchService
{
    public function isConfigured(): bool
    {
        return filled((string) config('services.openai.api_key'));
    }

    public function rankBusinesses(string $query, Collection $businesses): Collection
    {
        $businesses = $businesses
            ->filter(fn ($business) => $business instanceof Business)
            ->values();

        if (! $this->isConfigured() || $businesses->isEmpty()) {
            return $businesses;
        }

        try {
            $queryEmbedding = $this->queryEmbedding($query);

            if ($queryEmbedding === []) {
                return $businesses;
            }

            $businessEmbeddings = $this->businessEmbeddings($businesses);
        } catch (\Throwable $exception) {
            Log::warning('OpenAI embedding search failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $businesses;
        }

        $items = $businesses
            ->map(function (Business $business) use ($businessEmbeddings, $queryEmbedding) {
                $score = $this->cosineSimilarity($queryEmbedding, $businessEmbeddings[$business->id] ?? []);
                $business->setAttribute('semantic_score', round($score, 4));

                return $business;
            })
            ->all();

        usort($items, function (Business $left, Business $right) {
            $leftScore = (float) $left->getAttribute('semantic_score');
            $rightScore = (float) $right->getAttribute('semantic_score');

            if ($leftScore !== $rightScore) {
                return $rightScore <=> $leftScore;
            }

            $leftLikes = (int) ($left->likes_count ?? 0);
            $rightLikes = (int) ($right->likes_count ?? 0);

            if ($leftLikes !== $rightLikes) {
                return $rightLikes <=> $leftLikes;
            }

            return strcasecmp((string) $left->name, (string) $right->name);
        });

        return collect($items)->values();
    }

    private function queryEmbedding(string $query): array
    {
        return Cache::remember(
            'openai_query_embedding_' . md5($this->modelName() . '|' . mb_strtolower(trim($query), 'UTF-8')),
            now()->addHours(6),
            fn () => $this->embedTexts([$query])[0] ?? []
        );
    }

    private function businessEmbeddings(Collection $businesses): array
    {
        $embeddings = [];
        $missingIds = [];
        $missingTexts = [];

        foreach ($businesses as $business) {
            $cacheKey = $this->businessCacheKey($business);
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && $cached !== []) {
                $embeddings[$business->id] = $cached;

                continue;
            }

            $missingIds[] = $business->id;
            $missingTexts[] = $this->documentFor($business);
        }

        if ($missingTexts !== []) {
            $fetched = $this->embedTexts($missingTexts);

            foreach ($missingIds as $index => $businessId) {
                $vector = $fetched[$index] ?? [];

                if ($vector === []) {
                    continue;
                }

                $business = $businesses->firstWhere('id', $businessId);

                if (! $business instanceof Business) {
                    continue;
                }

                $embeddings[$businessId] = $vector;
                Cache::put($this->businessCacheKey($business), $vector, now()->addDay());
            }
        }

        return $embeddings;
    }

    private function embedTexts(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $payload = [
            'model' => $this->modelName(),
            'input' => array_values($texts),
            'encoding_format' => 'float',
        ];

        $dimensions = config('services.openai.embedding_dimensions');

        if (is_int($dimensions) && $dimensions > 0) {
            $payload['dimensions'] = $dimensions;
        }

        $response = Http::withToken((string) config('services.openai.api_key'))
            ->acceptJson()
            ->timeout((int) config('services.openai.timeout', 20))
            ->post('https://api.openai.com/v1/embeddings', $payload)
            ->throw();

        $items = data_get($response->json(), 'data', []);
        $vectors = [];

        foreach ($items as $item) {
            $index = (int) ($item['index'] ?? 0);
            $embedding = $item['embedding'] ?? [];

            if (is_array($embedding) && $embedding !== []) {
                $vectors[$index] = array_map('floatval', $embedding);
            }
        }

        ksort($vectors);

        return array_values($vectors);
    }

    private function documentFor(Business $business): string
    {
        $parts = array_filter([
            'name: ' . $this->compactText($business->name),
            'description: ' . $this->compactText($business->description),
            'category: ' . $this->compactText($business->category?->name),
            'parent_category: ' . $this->compactText($business->category?->parent?->name),
            'owner: ' . $this->compactText($business->owner_name),
            'keywords: ' . $this->compactText($this->implodeValues($business->keywords)),
            'services: ' . $this->compactText($this->implodeValues($business->services)),
            'offers: ' . $this->compactText($this->implodeValues($business->offers)),
            'address: ' . $this->compactText($business->address),
            'area: ' . $this->compactText($business->area?->name),
            'district: ' . $this->compactText($business->district?->name),
            'city: ' . $this->compactText($business->city?->name),
        ], fn (string $value) => ! str_ends_with($value, ': '));

        return Str::limit(implode(' | ', $parts), 2000, '');
    }

    private function cosineSimilarity(array $left, array $right): float
    {
        if ($left === [] || $right === [] || count($left) !== count($right)) {
            return 0.0;
        }

        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach ($left as $index => $value) {
            $rightValue = $right[$index];
            $dot += $value * $rightValue;
            $leftMagnitude += $value ** 2;
            $rightMagnitude += $rightValue ** 2;
        }

        if ($leftMagnitude <= 0.0 || $rightMagnitude <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }

    private function businessCacheKey(Business $business): string
    {
        return 'openai_business_embedding_' . md5(json_encode([
            'model' => $this->modelName(),
            'id' => $business->id,
            'updated_at' => optional($business->updated_at)?->timestamp,
        ]));
    }

    private function modelName(): string
    {
        return (string) config('services.openai.embedding_model', 'text-embedding-3-small');
    }

    private function implodeValues(mixed $value): string
    {
        if (! is_array($value)) {
            return $this->compactText($value);
        }

        $flattened = [];

        array_walk_recursive($value, function ($item) use (&$flattened) {
            if (is_scalar($item) || $item instanceof \Stringable) {
                $flattened[] = (string) $item;
            }
        });

        return implode(', ', array_filter(array_map(fn ($item) => trim($item), $flattened)));
    }

    private function compactText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    }
}