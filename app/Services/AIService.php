<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AIService
{
    /**
     * Generate a chart configuration from a natural language prompt.
     * Returns an associative array parsed from the AI JSON response.
     *
     * If OPENAI_API_KEY is not configured, this method will attempt to parse
     * the prompt as raw JSON for local testing.
     */
    public function generateChartConfig(string $prompt): array
    {
        //
        $driver = (string) config('services.ai.driver', env('AI_DRIVER', 'openai'));
        $apiKey = (string) config("services.ai.{$driver}.key");
        $model = (string) config("services.ai.{$driver}.model");

        // Local fallback: if no API key, treat prompt as already-formed JSON,
        // otherwise return a sensible default config so the UI keeps working.
        if (empty($apiKey)) {
            $decoded = json_decode($prompt, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // Default: leads created per day over last 30 days
            return [
                'chart_type' => 'line',
                'query' => [
                    'model' => 'Lead',
                    'group_by' => 'DATE(created_at)',
                    'aggregate_function' => 'count',
                    'aggregate_column' => 'id',
                    'joins' => [],
                    'filters' => [],
                    'time_filter' => [
                        'column' => 'created_at',
                        'period' => 'last_30_days',
                    ],
                ],
                'options' => [
                    'title' => 'Lead Giornalieri (Fallback - Ultimi 30 Giorni)',
                    'description' => 'Config predefinita usata in assenza di OPENAI_API_KEY o JSON valido.',
                    'x_axis_label' => 'Data',
                    'y_axis_label' => 'Numero di Lead',
                ],
            ];
        }

        $system = <<<'PROMPT'
Sei un assistente AI esperto di analisi dati. Genera SOLO un oggetto JSON valido che configuri un grafico per Chart.js secondo questo schema:
{
  "chart_type": "line|bar|pie|doughnut",
  "query": {
    "model": "Lead|Call",
    "group_by": "SQL expression (e.g., DATE(created_at))",
    "aggregate_function": "count|sum|avg",
    "aggregate_column": "id|<column>",
    "joins": [],
    "filters": [{"field":"<field>","operator":"=|!=|>|<|>=|<=|like|in","value":<value>}],
    "time_filter": {"column":"created_at|called_at","period":"last_7_days|last_30_days|last_12_months"}
  },
  "options": {
    "title": "...",
    "description": "...",
    "x_axis_label": "...",
    "y_axis_label": "..."
  }
}
Rispondi con SOLO JSON, senza testo aggiuntivo.
PROMPT;

        if ($driver === 'openai') {
            $baseUrl = (string) config('services.ai.openai.base_url', 'https://api.openai.com/v1');
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                ]);
            if (!$response->ok()) {
                throw new \RuntimeException('AI request failed: ' . $response->status() . ' ' . $response->body());
            }
            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        } elseif ($driver === 'gemini') {
            $baseUrl = (string) config('services.ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
            $response = Http::acceptJson()
                ->asJson()
                ->post(rtrim($baseUrl, '/') . "/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $system],
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]);
            if (!$response->ok()) {
                throw new \RuntimeException('AI request failed: ' . $response->status() . ' ' . $response->body());
            }
            // Gemini response content path
            $content = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        } else {
            throw new \InvalidArgumentException('Unsupported AI driver: ' . $driver);
        }
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('AI response was not valid JSON.');
        }
        return $decoded;
    }
}
