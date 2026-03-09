<?php

namespace TracyTran\EloquentTranslate\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TracyTran\EloquentTranslate\Contracts\AIServiceContract;

class ClaudeService implements AIServiceContract
{
    private string $baseUrl = 'https://api.anthropic.com/v1';

    private string $defaultModel;

    private string $version;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) config('eloquent-translate.ai.drivers.claude.api_key');
        $this->defaultModel = (string) config('eloquent-translate.ai.drivers.claude.api_model');
        $this->version = (string) config('eloquent-translate.ai.drivers.claude.api_version');
    }

    public function chat(string $prompt, array $options = []): AIResponse
    {
        $start = microtime(true);
        $payload = $this->payloadData($prompt, $options);
        $response = $this->sendRequest('messages', $payload, $options['timeout'] ?? 500);

        return $this->createAIResponse($response->json(), microtime(true) - $start);
    }

    public function chatPool(array $prompts, array $options = []): array
    {
        if (empty($prompts)) {
            return [];
        }

        $start = microtime(true);
        $timeout = $options['timeout'] ?? 500;

        $responses = Http::pool(function (Pool $pool) use ($prompts, $options, $timeout) {
            $requests = [];

            foreach ($prompts as $key => $prompt) {
                $requests[$key] = $pool
                    ->as((string) $key)
                    ->withHeaders($this->headers())
                    ->timeout($timeout)
                    ->post("{$this->baseUrl}/messages", $this->payloadData($prompt, $options));
            }

            return $requests;
        });

        $duration = microtime(true) - $start;
        $results = [];

        foreach ($prompts as $key => $prompt) {
            $response = $responses[$key];
            $this->ensureSuccessfulResponse($response, 'messages', $key);
            $results[$key] = $this->createAIResponse($response->json(), $duration);
        }

        return $results;
    }

    public function countTokens(string $prompt, array $options = []): int
    {
        $payload = $this->payloadData($prompt, $options);
        unset($payload['max_tokens'], $payload['temperature']);

        $response = $this->sendRequest('messages/count_tokens', $payload, $options['timeout'] ?? 30);
        $data = $response->json();

        return (int) ($data['input_tokens'] ?? 0);
    }

    public function healthCheck(): bool
    {
        try {
            return $this->chat('Hello', ['max_tokens' => 10])->content !== '';
        } catch (\Throwable $exception) {
            Log::error('Claude health check failed', ['error' => $exception->getMessage()]);

            return false;
        }
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'Anthropic Claude',
            'default_model' => $this->defaultModel,
            'version' => $this->version,
        ];
    }

    private function payloadData(string $prompt, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? $this->defaultModel,
            'max_tokens' => $options['max_tokens'] ?? 5000,
            'temperature' => $options['temperature'] ?? 0.5,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        if (! empty($options['system'])) {
            $payload['system'] = $options['system'];
        }

        return $payload;
    }

    private function sendRequest(string $endpoint, array $payload, int $timeout): Response
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($timeout)
            ->post("{$this->baseUrl}/{$endpoint}", $payload);

        $this->ensureSuccessfulResponse($response, $endpoint);

        return $response;
    }

    private function headers(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->version,
            'content-type' => 'application/json',
        ];
    }

    private function createAIResponse(array $data, float $duration): AIResponse
    {
        return new AIResponse(
            content: $data['content'][0]['text'] ?? '',
            model: $data['model'] ?? $this->defaultModel,
            inputTokens: (int) ($data['usage']['input_tokens'] ?? 0),
            outputTokens: (int) ($data['usage']['output_tokens'] ?? 0),
            duration: $duration,
            metadata: ['stop_reason' => $data['stop_reason'] ?? null],
        );
    }

    private function ensureSuccessfulResponse(Response $response, string $endpoint, string|int|null $requestKey = null): void
    {
        if (! $response->successful()) {
            Log::error('Claude API Error', [
                'endpoint' => $endpoint,
                'request_key' => $requestKey,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Claude API request failed ({$endpoint}): ".$response->body());
        }
    }
}