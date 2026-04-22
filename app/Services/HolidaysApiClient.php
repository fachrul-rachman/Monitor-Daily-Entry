<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HolidaysApiClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchIndonesia(int $year, ?string $type = null): array
    {
        $base = rtrim((string) config('holidays.base_url'), '/').'/';
        $apiKey = (string) config('holidays.api_key');
        $type = $type ?? (string) config('holidays.default_type');

        if ($apiKey === '') {
            throw new \RuntimeException('Missing holidays API key (HOLIDAYS_API_CO_ID).');
        }

        $all = [];
        $page = 1;

        while (true) {
            $resp = Http::acceptJson()
                ->timeout(20)
                ->withHeaders([
                    'x-api-co-id' => $apiKey,
                ])
                ->get($base, [
                    'year' => $year,
                    'type' => $type,
                    'page' => $page,
                ]);

            if (! $resp->successful()) {
                $body = (string) $resp->body();
                $snippet = $body !== '' ? ' Body: '.mb_substr($body, 0, 300) : '';
                throw new \RuntimeException('Holidays API request failed with status '.$resp->status().'.'.$snippet);
            }

            $json = $resp->json();
            if (! is_array($json)) {
                throw new \RuntimeException('Holidays API response is not JSON object.');
            }

            $data = $json['data'] ?? null;
            if (! is_array($data)) {
                throw new \RuntimeException('Holidays API response missing data array.');
            }

            foreach ($data as $row) {
                if (is_array($row)) {
                    $all[] = $row;
                }
            }

            $paging = $json['paging'] ?? null;
            $totalPage = is_array($paging) ? (int) ($paging['total_page'] ?? 1) : 1;

            if ($page >= max($totalPage, 1)) {
                break;
            }

            $page++;
        }

        return $all;
    }
}

