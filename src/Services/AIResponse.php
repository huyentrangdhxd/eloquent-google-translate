<?php

namespace TracyTran\EloquentTranslate\Services;

final readonly class AIResponse
{
    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public float $duration,
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        try {
            $parsedContent = $this->parseJson($this->content);
        } catch (\Throwable) {
            $parsedContent = $this->content;
        }

        return [
            'content' => $parsedContent,
            'model' => $this->model,
            'usage' => [
                'input_tokens' => $this->inputTokens,
                'output_tokens' => $this->outputTokens,
                'total_tokens' => $this->inputTokens + $this->outputTokens,
            ],
            'duration' => $this->duration,
            'metadata' => $this->metadata,
        ];
    }

    private function parseJson(string $content): array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $data = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse AI response: '.json_last_error_msg());
        }

        return $data;
    }
}