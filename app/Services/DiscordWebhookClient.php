<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DiscordWebhookClient
{
    /**
     * @param array<string, mixed> $payload
     */
    public function send(string $webhookUrl, array $payload): void
    {
        if (trim($webhookUrl) === '') {
            throw new \RuntimeException('Discord webhook URL is empty.');
        }

        $resp = Http::timeout(10)->post($webhookUrl, $payload);

        if (! $resp->successful()) {
            $body = trim((string) $resp->body());
            $snippet = $body !== '' ? ' Body: '.mb_substr($body, 0, 500) : '';

            throw new \RuntimeException('Discord webhook failed with status '.$resp->status().'.'.$snippet);
        }
    }
}
