<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIResponsesClient
{
    public function respondJson(string $system, string $user, array $context = []): string
    {
        $apiKey = (string) config('openai.api_key');
        $model = (string) config('openai.model');

        if ($apiKey === '' || $model === '') {
            throw new \RuntimeException('Missing OpenAI configuration.');
        }

        $payload = [
            'model' => $model,
            // Keep it deterministic for executive summary.
            'temperature' => 0.2,
            'max_output_tokens' => 600,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        ['type' => 'input_text', 'text' => $system],
                    ],
                ],
                [
                    'role' => 'system',
                    'content' => [
                        ['type' => 'input_text', 'text' => 'Context snapshot (JSON): '.json_encode($context, JSON_UNESCAPED_UNICODE)],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $user],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            $message = (string) ($response->json('error.message') ?? '');
            $code = (string) ($response->json('error.code') ?? '');
            $param = (string) ($response->json('error.param') ?? '');

            $detail = trim(implode(' | ', array_filter([
                $message ? 'msg: '.$message : null,
                $code ? 'code: '.$code : null,
                $param ? 'param: '.$param : null,
            ])));

            throw new \RuntimeException('OpenAI request failed: '.$response->status().($detail ? ' ('.$detail.')' : ''));
        }

        $json = $response->json();

        // Responses API typically returns "output_text" for convenience.
        $text = $json['output_text'] ?? null;
        if (is_string($text) && $text !== '') {
            return $text;
        }

        // Fallback: try to extract text from output array.
        $output = $json['output'] ?? [];
        if (is_array($output)) {
            foreach ($output as $item) {
                if (! is_array($item)) continue;
                $content = $item['content'] ?? null;
                if (! is_array($content)) continue;
                foreach ($content as $c) {
                    if (is_array($c) && ($c['type'] ?? null) === 'output_text' && isset($c['text']) && is_string($c['text'])) {
                        return $c['text'];
                    }
                }
            }
        }

        throw new \RuntimeException('OpenAI response did not contain text.');
    }
}
