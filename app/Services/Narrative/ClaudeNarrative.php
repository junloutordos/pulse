<?php

namespace App\Services\Narrative;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Optional AI-assisted summary. Sends aggregate figures only (see payload()) and returns null on any failure so the template is used. */
class ClaudeNarrative
{
    private const SYSTEM = 'You write a neutral two or three sentence summary of one municipality for an education planning brief. '
        .'Use only the figures provided. Do not speculate about causes, do not infer anything about individuals, and do not recommend anything beyond the suggested action.';

    /**
     * The only fields that leave the server. No codes, no free text typed by staff, no names of people.
     *
     * @param  array<string, mixed>  $d
     * @return array<string, mixed>
     */
    public static function payload(array $d): array
    {
        return [
            'municipality' => $d['municipality']->name,
            'province' => $d['municipality']->province,
            'status' => $d['status'],
            'rank' => $d['rank'],
            'ranked_count' => $d['rankedCount'],
            'observations' => $d['observations'],
            'components' => $d['components'],
            'weights' => $d['weights'],
            'suggested_action' => $d['action'],
        ];
    }

    /** @param array<string, mixed> $d */
    public function summarize(array $d): ?string
    {
        try {
            $response = Http::withHeaders(['x-api-key' => config('services.anthropic.key'), 'anthropic-version' => '2023-06-01'])
                ->timeout(15)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-sonnet-5-5'),
                    'max_tokens' => 300,
                    'system' => self::SYSTEM,
                    'messages' => [['role' => 'user', 'content' => json_encode(self::payload($d))]],
                ]);

            $text = $response->successful() ? trim((string) data_get($response->json(), 'content.0.text')) : '';

            return $text !== '' ? $text : null;
        } catch (Throwable) {
            return null;
        }
    }
}
