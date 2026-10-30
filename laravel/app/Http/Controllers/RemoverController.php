<?php

namespace App\Http\Controllers;

use App\Models\CollectedPost;
use App\Services\GraphApi;
use Illuminate\Http\Request;

class RemoverController extends Controller
{
    public function __construct(private GraphApi $graph)
    {
    }

    private function mask(?string $t): string
    {
        if (!$t) {
            return '';
        }
        return strlen($t) > 10 ? substr($t, 0, 4) . '••••••••' . substr($t, -4) : '••••••••';
    }

    private function postsFor(?string $pageId): array
    {
        if (!$pageId) {
            return [];
        }
        return CollectedPost::where('page_id', $pageId)
            ->orderByDesc('created_time')->get()
            ->map(fn ($p) => [
                'id' => $p->post_id,
                'message' => $p->message,
                'created_time' => $p->created_time?->toIso8601String(),
            ])->all();
    }

    private function fail(string $msg, int $status = 422)
    {
        return response()->json(['ok' => false, 'error' => $msg], $status);
    }

    public function index()
    {
        return view('dashboard', [
            'hasUserToken' => session()->has('fb.user_token'),
            'userMask' => $this->mask(session('fb.user_token')),
            'pageId' => session('fb.page_id', ''),
            'hasPageToken' => session()->has('fb.page_token'),
            'pageMask' => $this->mask(session('fb.page_token')),
            'pages' => collect(session('fb.pages', []))
                ->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name']])->values(),
            'posts' => $this->postsFor(session('fb.page_id')),
        ]);
    }

    public function saveToken(Request $r)
    {
        $data = $r->validate(['token' => 'required|string|min:20|max:1000']);
        session(['fb.user_token' => trim($data['token'])]);
        session()->forget('fb.pages');
        return response()->json(['ok' => true, 'mask' => $this->mask($data['token'])]);
    }

    public function loadPages()
    {
        $token = session('fb.user_token');
        if (!$token) {
            return $this->fail('Paste and save your User Access Token first.');
        }

        $res = $this->graph->get(GraphApi::BASE . '/me/accounts', $token, ['fields' => 'id,name,access_token', 'limit' => 100]);
        if (!$res['ok']) {
            return $this->fail($res['error']);
        }

        $pages = collect($res['data']['data'] ?? [])
            ->filter(fn ($p) => !empty($p['id']) && !empty($p['access_token']))
            ->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name'] ?? $p['id'], 'token' => $p['access_token']])
            ->values()->all();
        session(['fb.pages' => $pages]);

        if (!$pages) {
            return $this->fail('No Pages found. You must be a Page admin and the token must have pages_show_list.');
        }

        return response()->json([
            'ok' => true,
            'pages' => array_map(fn ($p) => ['id' => $p['id'], 'name' => $p['name']], $pages),
        ]);
    }

    public function selectPage(Request $r)
    {
        $data = $r->validate(['page_id' => 'required|string|max:64']);
        $page = collect(session('fb.pages', []))->firstWhere('id', $data['page_id']);
        if (!$page) {
            return $this->fail('Page not found. Click Load My Pages again.');
        }
        session(['fb.page_id' => $page['id'], 'fb.page_token' => $page['token']]);

        return response()->json([
            'ok' => true,
            'page_id' => $page['id'],
            'page_mask' => $this->mask($page['token']),
            'posts' => $this->postsFor($page['id']),
        ]);
    }

    /** Editable Page ID / Page Token fields. */
    public function manualPage(Request $r)
    {
        $data = $r->validate([
            'page_id' => ['required', 'string', 'max:64', 'regex:/^[0-9A-Za-z_.-]+$/'],
            'token' => 'nullable|string|max:1000',
        ]);
        session(['fb.page_id' => $data['page_id']]);
        if (!empty($data['token'])) {
            session(['fb.page_token' => trim($data['token'])]);
        }

        return response()->json([
            'ok' => true,
            'page_mask' => $this->mask(session('fb.page_token')),
            'has_page_token' => session()->has('fb.page_token'),
            'posts' => $this->postsFor($data['page_id']),
        ]);
    }

    public function loadPosts()
    {
        set_time_limit(180);
        $pageId = session('fb.page_id');
        $token = session('fb.page_token');
        if (!$pageId || !$token) {
            return $this->fail('Choose a Page (or enter Page ID and Page Access Token) first.');
        }

        $out = $this->graph->collect($pageId, $token);
        $now = now();
        $rows = array_map(fn ($p) => $p + ['page_id' => $pageId, 'created_at' => $now, 'updated_at' => $now], $out['posts']);

        // Clean result: replace the stored list. If an edge failed, only add, never drop.
        if (!$out['errors']) {
            CollectedPost::where('page_id', $pageId)->delete();
        }
        foreach (array_chunk($rows, 300) as $chunk) {
            CollectedPost::upsert($chunk, ['page_id', 'post_id'], ['message', 'created_time', 'updated_at']);
        }

        $counts = $out['counts'];
        $counts['total'] = count($out['posts']);

        return response()->json([
            'ok' => true,
            'counts' => $counts,
            'errors' => $out['errors'],
            'posts' => $this->postsFor($pageId),
        ]);
    }

    public function clearPosts()
    {
        if ($pageId = session('fb.page_id')) {
            CollectedPost::where('page_id', $pageId)->delete();
        }
        return response()->json(['ok' => true]);
    }

    public function exportPosts()
    {
        $posts = $this->postsFor(session('fb.page_id'));
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($posts, $safe) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['id', 'created_time', 'message']);
            foreach ($posts as $p) {
                fputcsv($f, [$p['id'], $p['created_time'], $safe($p['message'])]);
            }
            fclose($f);
        }, 'collected-posts.csv', ['Content-Type' => 'text/csv']);
    }

    public function deletePost(Request $r)
    {
        $data = $r->validate(['id' => 'required|string|max:128']);
        $pageId = session('fb.page_id');
        $token = session('fb.page_token');
        if (!$pageId || !$token) {
            return $this->fail('Session expired. Add your Page token again.');
        }

        $post = CollectedPost::where('page_id', $pageId)->where('post_id', $data['id'])->first();
        if (!$post) {
            return response()->json(['ok' => false, 'rate_limited' => false, 'error' => 'Post is not in the collected list.']);
        }

        $res = $this->graph->delete($data['id'], $token);
        if ($res['ok']) {
            $post->delete();
        }
        return response()->json($res);
    }

    public function logout()
    {
        session()->flush();
        return redirect('/');
    }
}
