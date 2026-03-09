<?php

namespace TracyTran\EloquentTranslate\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TracyTran\EloquentTranslate\Contracts\AIServiceContract;

class GeminiService implements AIServiceContract
{
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    private string $defaultModel;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) config('eloquent-translate.ai.drivers.gemini.api_key');
        $this->defaultModel = (string) config('eloquent-translate.ai.drivers.gemini.api_model');
    }

    public function chat(string $prompt, array $options = []): AIResponse
    {
        $start = microtime(true);
        $response = $this->sendRequest($prompt, $options);

        return $this->createAIResponse($response->json(), microtime(true) - $start);
    }

    public function chatPool(array $prompts, array $options = []): array
    {
        if (empty($prompts)) {
            return [];
        }

        $start = microtime(true);
        $timeout = $options['timeout'] ?? 500;
        $endpoint = $this->generateContentEndpoint($options);

        $responses = Http::pool(function (Pool $pool) use ($prompts, $options, $timeout, $endpoint) {
            $requests = [];

            foreach ($prompts as $key => $prompt) {
                $requests[$key] = $pool
                    ->as((string) $key)
                    ->withHeaders($this->headers())
                    ->timeout($timeout)
                    ->post($endpoint, $this->payloadData($prompt, $options));
            }

            return $requests;
        });

        $duration = microtime(true) - $start;
        $results = [];

        foreach ($prompts as $key => $prompt) {
            $response = $responses[$key];
            $this->ensureSuccessfulResponse($response, $endpoint, $key);
            $results[$key] = $this->createAIResponse($response->json(), $duration);
        }

        return $results;
    }

    public function countTokens(string $prompt, array $options = []): int
    {
        $model = $options['model'] ?? $this->defaultModel;
        $endpoint = "{$this->baseUrl}/{$model}:countTokens";

        $response = Http::withHeaders($this->headers())->timeout($options['timeout'] ?? 120)->post($endpoint, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        $this->ensureSuccessfulResponse($response, $endpoint, null, 'Gemini count tokens failed');

        return (int) ($response->json()['totalTokens'] ?? 0);
    }

    public function healthCheck(): bool
    {
        try {
            return $this->chat('Hello', ['max_output_tokens' => 10])->content !== '';
        } catch (\Throwable $exception) {
            Log::error('Gemini health check failed', ['error' => $exception->getMessage()]);

            return false;
        }
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'Google Gemini',
            'default_model' => $this->defaultModel,
        ];
    }

    private function sendRequest(string $prompt, array $options = []): Response
    {
        $endpoint = $this->generateContentEndpoint($options);

        $response = Http::withHeaders($this->headers())
            ->timeout($options['timeout'] ?? 120)
            ->post($endpoint, $this->payloadData($prompt, $options));

        $this->ensureSuccessfulResponse($response, $endpoint);

        return $response;
    }

    private function generateContentEndpoint(array $options = []): string
    {
        $model = $options['model'] ?? $this->defaultModel;

        return "{$this->baseUrl}/{$model}:generateContent";
    }

    private function payloadData(string $prompt, array $options = []): array
    {
        $generationConfig = array_filter([
            'temperature' => $options['temperature'] ?? null,
            'maxOutputTokens' => $options['max_output_tokens'] ?? $options['max_tokens'] ?? null,
        ], fn ($value) => ! is_null($value));

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        if (! empty($generationConfig)) {
            $payload['generationConfig'] = $generationConfig;
        }

        return $payload;
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $this->apiKey,
        ];
    }

    private function createAIResponse(array $data, float $duration): AIResponse
    {
        return new AIResponse(
            content: $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            model: $data['modelVersion'] ?? $this->defaultModel,
            inputTokens: (int) ($data['usageMetadata']['promptTokenCount'] ?? 0),
            outputTokens: (int) (($data['usageMetadata']['candidatesTokenCount'] ?? 0) + ($data['usageMetadata']['thoughtsTokenCount'] ?? 0)),
            duration: $duration,
            metadata: ['response_id' => $data['responseId'] ?? null],
        );
    }

    private function ensureSuccessfulResponse(
        Response $response,
        string $endpoint,
        string|int|null $requestKey = null,
        string $message = 'Gemini API Error'
    ): void {
        if (! $response->successful()) {
            Log::error($message, [
                'endpoint' => $endpoint,
                'request_key' => $requestKey,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Gemini API request failed ({$endpoint}): ".$response->body());
        }
    }
}