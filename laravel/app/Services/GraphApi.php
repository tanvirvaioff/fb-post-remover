<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GraphApi
{
    public const BASE = 'https://graph.facebook.com/v21.0';

    /** Remove any token text from a message before it goes anywhere. */
    private function clean(?string $text, string $token): string
    {
        $text = (string) $text;
        return $token !== '' ? str_replace($token, '[hidden]', $text) : $text;
    }

    public function get(string $url, string $token, array $query = []): array
    {
        try {
            $res = Http::withToken($token)->acceptJson()->timeout(30)->get($url, $query);
        } catch (ConnectionException $e) {
            return ['ok' => false, 'data' => [], 'error' => 'Network error while contacting the Graph API.'];
        }

        $data = $res->json() ?? [];
        if ($res->successful()) {
            return ['ok' => true, 'data' => $data, 'error' => null];
        }

        $msg = $data['error']['message'] ?? ('HTTP ' . $res->status());
        $code = $data['error']['code'] ?? null;
        return ['ok' => false, 'data' => $data, 'error' => $this->clean($msg . ($code ? " (code $code)" : ''), $token)];
    }

    /** paging.next links can carry an access_token query param: strip it, we use the Bearer header. */
    private function cleanNext(?string $next): ?string
    {
        if (!$next) {
            return null;
        }
        $parts = parse_url($next);
        parse_str($parts['query'] ?? '', $q);
        unset($q['access_token']);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'graph.facebook.com')
            . ($parts['path'] ?? '') . ($q ? '?' . http_build_query($q) : '');
    }

    /** Collect from posts + feed + published_posts, merged and de-duplicated by post ID. */
    public function collect(string $pageId, string $token): array
    {
        $all = [];
        $counts = [];
        $errors = [];

        foreach (['posts', 'feed', 'published_posts'] as $edge) {
            $counts[$edge] = 0;
            $url = self::BASE . '/' . rawurlencode($pageId) . '/' . $edge;
            $query = ['fields' => 'id,message,created_time', 'limit' => 100];

            for ($page = 0; $page < 3 && $url; $page++) {
                $r = $this->get($url, $token, $query);
                $query = [];
                if (!$r['ok']) {
                    $errors[$edge] = $r['error'];
                    break;
                }
                foreach ($r['data']['data'] ?? [] as $row) {
                    if (empty($row['id']) || isset($all[$row['id']])) {
                        continue;
                    }
                    $counts[$edge]++;
                    $all[$row['id']] = [
                        'post_id' => $row['id'],
                        'message' => $row['message'] ?? null,
                        'created_time' => isset($row['created_time']) ? Carbon::parse($row['created_time'])->utc() : null,
                    ];
                }
                $url = $this->cleanNext($r['data']['paging']['next'] ?? null);
            }
        }

        return ['posts' => array_values($all), 'counts' => $counts, 'errors' => $errors];
    }

    /** One DELETE call. Returns ok / rate_limited / error. Never includes the token. */
    public function delete(string $postId, string $token): array
    {
        try {
            $res = Http::withToken($token)->acceptJson()->timeout(30)
                ->delete(self::BASE . '/' . rawurlencode($postId));
        } catch (ConnectionException $e) {
            return ['ok' => false, 'rate_limited' => false, 'error' => 'Network error while contacting the Graph API.'];
        }

        $data = $res->json() ?? [];
        if ($res->successful() && (($data['success'] ?? false) === true || ($data['deleted'] ?? false) === true)) {
            return ['ok' => true, 'rate_limited' => false, 'error' => null];
        }

        $msg = $data['error']['message'] ?? 'Delete returned deleted=false.';
        $code = $data['error']['code'] ?? null;
        $rate = (string) $code === '368'
            || str_contains((string) $msg, '368')
            || (bool) preg_match('/\b(rate|limit)/i', (string) $msg);

        return [
            'ok' => false,
            'rate_limited' => $rate,
            'error' => $this->clean($msg . ($code ? " (code $code)" : ''), $token),
        ];
    }
}
