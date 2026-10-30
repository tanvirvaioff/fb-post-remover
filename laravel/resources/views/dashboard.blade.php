<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>FB Page Post Remover | JioPaglaLtd</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
  body { font-family: Inter, system-ui, -apple-system, 'Segoe UI', sans-serif; background:#08080d; color:#f1f5f9; }
  [x-cloak] { display:none !important; }
  .brand-grad { background: radial-gradient(600px 200px at 85% 0%, rgba(79,124,255,.45), transparent 60%), linear-gradient(135deg, #000, #11111c 55%, #16224d); border-bottom:1px solid #26262e; }
  .card { background:linear-gradient(180deg,#15151f,#12121a); border:1px solid #26262e; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,.45); padding:1.5rem; color:#f1f5f9; }
  .btn { border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.875rem; transition:opacity .15s, transform .15s; }
  .btn:disabled { opacity:.45; cursor:not-allowed; }
  .btn-primary { color:#fff; background:linear-gradient(135deg,#4f7cff,#00c2ff); box-shadow:0 8px 20px rgba(79,124,255,.35); }
  .btn-light { background:#26262e; color:#fff; }
  .btn-danger { background:linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; box-shadow:0 8px 20px rgba(220,38,38,.25); }
  .btn-stop { background:#0f172a; color:#fff; }
  .field { width:100%; border:1px solid #33333f; border-radius:12px; padding:.55rem .75rem; font-size:.875rem; background:#1c1c28; color:#f1f5f9; }
  .field:focus { outline:none; border-color:#4f7cff; box-shadow:0 0 0 3px rgba(79,124,255,.25); } .field::placeholder{color:#64748b}
  .lbl { display:block; font-size:.8rem; font-weight:700; margin-bottom:.25rem; color:#cbd5e1; }
  .bar { height:16px; border-radius:999px; background:#26262e; overflow:hidden; }
  .bar > div { height:100%; border-radius:999px; background:linear-gradient(90deg,#4f7cff,#00c2ff); transition:width .35s ease; }
  .spin { width:16px; height:16px; border:2px solid #26262e; border-top-color:#00c2ff; border-radius:50%; animation:sp .8s linear infinite; display:inline-block; }
  @keyframes sp { to { transform:rotate(360deg); } }
  @media (prefers-reduced-motion: reduce) { .spin { animation-duration:2s; } .bar > div { transition:none; } }
  /* Black Premium overrides for light Tailwind utilities in the markup */
  .bg-slate-50{background:#0d0d14 !important;border:1px solid #26262e}
  .text-slate-500{color:#94a3b8 !important}.text-slate-600{color:#cbd5e1 !important}.text-slate-700{color:#e2e8f0 !important}
  .border-slate-100{border-color:#26262e !important}.hover\:bg-slate-50:hover{background:#17171f !important}
  .text-red-600{color:#f87171 !important}.text-emerald-600{color:#34d399 !important}.text-amber-700{color:#fbbf24 !important}
</style>
</head>
<body>
<header class="brand-grad text-white">
  <div class="max-w-[900px] mx-auto px-4 py-5 flex items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold leading-tight">FB Page Post Remover</h1>
      <p class="text-sm opacity-90">by JioPaglaLtd</p><span style="display:inline-block;margin-top:6px;font-size:10px;letter-spacing:1px;font-weight:800;padding:4px 8px;border-radius:999px;background:rgba(0,194,255,.15);border:1px solid rgba(0,194,255,.4);color:#7dd3fc">PRO • BLACK PREMIUM</span>
    </div>
    <form method="POST" action="/logout">@csrf
      <button class="btn bg-white/20 hover:bg-white/30 text-white">Log out and clear tokens</button>
    </form>
  </div>
</header>

<main class="max-w-[900px] mx-auto px-4 py-6 space-y-5" x-data="remover()" x-cloak>

  <!-- Card 1: token + pages -->
  <section class="card space-y-4">
    <h2 class="text-lg font-semibold">1. Connect your Page</h2>

    <div>
      <label class="lbl">User Access Token</label>
      <div class="flex gap-2">
        <input type="password" class="field" x-model="userToken" autocomplete="off" spellcheck="false"
               :placeholder="userMask ? 'Saved: ' + userMask : 'Paste your User Access Token'">
        <button class="btn btn-light shrink-0" :disabled="locked || userToken.length < 20" x-on:click="saveToken()">Save token</button>
      </div>
      <p class="text-xs text-slate-500 mt-1">Needs: pages_show_list, pages_read_engagement, pages_manage_posts, pages_manage_metadata. Kept in your encrypted session only.</p>
    </div>

    <div class="flex flex-wrap gap-2 items-end">
      <button class="btn btn-primary" :disabled="locked || !userMask" x-on:click="loadPages()">Load My Pages</button>
      <div class="grow min-w-[200px]" x-show="pages.length">
        <label class="lbl">Page</label>
        <select class="field" x-model="selectedPage" x-on:change="choosePage()" :disabled="locked">
          <option value="">Choose a Page</option>
          <template x-for="p in pages" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
        </select>
      </div>
    </div>
    <p class="text-sm text-red-600" x-show="pagesError" x-text="pagesError"></p>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="lbl">Page ID</label>
        <input class="field" x-model="pageId" placeholder="YOUR_PAGE_ID" autocomplete="off">
      </div>
      <div>
        <label class="lbl">Page Access Token</label>
        <input type="password" class="field" x-model="pageToken" autocomplete="off" spellcheck="false"
               :placeholder="pageMask ? 'Saved: ' + pageMask : 'Auto-filled after you choose a Page'">
      </div>
    </div>
    <button class="btn btn-light" :disabled="locked || !pageId" x-on:click="savePage()">Save Page settings</button>
    <p class="text-sm text-red-600" x-show="pageError" x-text="pageError"></p>
  </section>

  <!-- Card 2: posts + delete controls -->
  <section class="card space-y-4">
    <h2 class="text-lg font-semibold">2. Posts</h2>

    <div class="flex flex-wrap items-center gap-3">
      <button class="btn btn-primary" :disabled="locked || !pageReady" x-on:click="loadPosts()">Load Posts</button>
      <span class="text-sm text-slate-600 flex items-center gap-2" x-show="loading">
        <span class="spin"></span> Detecting posts via posts / feed / published_posts...
      </span>
    </div>
    <p class="text-sm font-medium text-slate-700" x-show="counts"
       x-text="counts ? `posts ${counts.posts}, feed ${counts.feed}, published_posts ${counts.published_posts}, total ${counts.total}` : ''"></p>
    <p class="text-sm text-red-600" x-show="loadError" x-text="loadError"></p>
    <template x-for="(msg, edge) in edgeErrors" :key="edge">
      <p class="text-xs text-amber-700" x-text="edge + ': ' + msg"></p>
    </template>

    <div class="flex flex-wrap gap-2" x-show="posts.length">
      <button class="btn btn-light" :disabled="locked" x-on:click="setAll(true)">Check All</button>
      <button class="btn btn-light" :disabled="locked" x-on:click="setAll(false)">Uncheck All</button>
      <a class="btn btn-light" href="/posts/export">Export CSV</a>
      <button class="btn btn-light" :disabled="locked" x-on:click="clearList()">Clear List</button>
      <span class="ml-auto text-sm text-slate-600 self-center" x-text="selectedCount() + ' of ' + posts.length + ' selected'"></span>
    </div>

    <div class="border border-slate-200 rounded-xl max-h-96 overflow-auto" x-show="posts.length">
      <table class="w-full text-sm">
        <tbody>
          <template x-for="p in posts" :key="p.id">
            <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
              <td class="pl-3 py-2 w-8"><input type="checkbox" class="w-4 h-4 accent-blue-600" x-model="selected[p.id]" :disabled="locked"></td>
              <td class="px-2 py-2 whitespace-nowrap text-slate-600" x-text="(p.created_time || '').slice(0,10)"></td>
              <td class="px-2 py-2">
                <div x-text="p.message ? p.message.slice(0,80) : '(no text)'"></div>
                <div class="text-xs text-slate-400" x-text="p.id"></div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
    <p class="text-sm text-slate-500" x-show="!posts.length && !loading">No posts in the list yet. Choose a Page, then click Load Posts.</p>

    <hr class="border-slate-200">

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div><label class="lbl">How many? (0 = all)</label><input type="number" min="0" class="field" x-model.number="howMany" :disabled="locked"></div>
      <div><label class="lbl">Delay (seconds, 0-30)</label><input type="number" min="0" max="30" class="field" x-model.number="delay" :disabled="locked"></div>
      <div>
        <label class="lbl">Workers (1-10)</label>
        <input type="number" min="1" max="10" class="field" x-model.number="workers" :disabled="locked">
      </div>
      <div><label class="lbl">Remove All: type REMOVE-ALL</label><input class="field" x-model="confirmText" placeholder="REMOVE-ALL" autocomplete="off" :disabled="locked"></div>
    </div>
    <p class="text-xs text-slate-500">Workers: 1 is safest and slowest, 3 is about 3x faster, 10 is fastest but triggers the most Meta errors. Deleting is permanent and cannot be undone.</p>

    <div class="flex flex-wrap gap-2">
      <button class="btn btn-danger" :disabled="locked || !selectedCount()" x-on:click="removeSelected()">Remove Selected</button>
      <button class="btn btn-danger" :disabled="locked || !posts.length || confirmText !== 'REMOVE-ALL'" x-on:click="removeAll()">Remove All</button>
      <button class="btn btn-light" :disabled="locked || !failedIds.length" x-on:click="retryFailed()">Retry Failed Only</button>
      <button class="btn btn-stop" :disabled="!running || stopped" x-on:click="stop()">Stop</button>
    </div>
  </section>

  <!-- Card 3: progress -->
  <section class="card space-y-3">
    <h2 class="text-lg font-semibold">3. Progress</h2>
    <div class="bar" role="progressbar" :aria-valuenow="percent()" aria-valuemin="0" aria-valuemax="100"><div :style="`width:${percent()}%`"></div></div>
    <div class="grid grid-cols-4 gap-2 text-center">
      <div class="rounded-xl bg-slate-50 py-2"><div class="text-xl font-bold" x-text="stats.total"></div><div class="text-xs text-slate-500">Total</div></div>
      <div class="rounded-xl bg-slate-50 py-2"><div class="text-xl font-bold text-emerald-600" x-text="stats.done"></div><div class="text-xs text-slate-500">Done</div></div>
      <div class="rounded-xl bg-slate-50 py-2"><div class="text-xl font-bold" x-text="remaining()"></div><div class="text-xs text-slate-500">Remaining</div></div>
      <div class="rounded-xl bg-slate-50 py-2"><div class="text-xl font-bold text-red-600" x-text="stats.failed"></div><div class="text-xs text-slate-500">Failed</div></div>
    </div>
    <p class="text-sm font-medium" :class="rateLimited ? 'text-red-600' : 'text-slate-700'" x-text="status || 'Idle. Nothing running.'"></p>
    <p class="text-xs text-slate-500" x-show="lastError">Last error: <span x-text="lastError"></span></p>
  </section>
</main>

<footer class="max-w-[900px] mx-auto px-4 pb-8 text-center text-sm text-slate-500">
  JioPaglaLtd · private admin tool · tokens live in your encrypted session only
</footer>

<script>
const sleep = ms => new Promise(r => setTimeout(r, ms));
const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, Number.isFinite(+v) ? +v : lo));

function remover() {
  return {
    userToken: '', pageToken: '',
    userMask: @json($userMask), pageMask: @json($pageMask),
    pageId: @json($pageId), pageReady: @json($hasPageToken && $pageId !== ''),
    pages: @json($pages), selectedPage: @json($pageId),
    posts: @json($posts), selected: {},
    counts: null, edgeErrors: {}, loading: false, busy: false,
    pagesError: '', pageError: '', loadError: '',
    howMany: 0, delay: 1, workers: 1, confirmText: '',
    running: false, stopped: false, rateLimited: false,
    failedIds: [], stats: { total: 0, done: 0, failed: 0 }, status: '', lastError: '',

    init() { this.posts.forEach(p => this.selected[p.id] = true); },
    get locked() { return this.running || this.busy || this.loading; },

    async api(url, body = {}) {
      try {
        const r = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          body: JSON.stringify(body),
        });
        let j = {}; try { j = await r.json(); } catch (e) {}
        if (r.status === 419) j = { ok: false, error: 'Session expired. Reload the page and try again.' };
        else if (!r.ok && !j.error) j = { ok: false, error: j.message || ('Request failed (' + r.status + ').') };
        return j;
      } catch (e) { return { ok: false, error: 'Network error. Check that the server is running.' }; }
    },
    errText(j) { return j.error || (j.errors ? Object.values(j.errors).flat()[0] : 'Something went wrong.'); },

    setPosts(list) { this.posts = list; this.selected = {}; list.forEach(p => this.selected[p.id] = true); },
    setAll(v) { this.posts.forEach(p => this.selected[p.id] = v); },
    selectedCount() { return this.posts.filter(p => this.selected[p.id]).length; },

    async saveToken() {
      this.busy = true; this.pagesError = '';
      const j = await this.api('/token', { token: this.userToken });
      if (j.ok) { this.userMask = j.mask; this.userToken = ''; this.pages = []; }
      else this.pagesError = this.errText(j);
      this.busy = false;
    },
    async loadPages() {
      this.busy = true; this.pagesError = '';
      const j = await this.api('/pages/load');
      if (j.ok) this.pages = j.pages; else { this.pages = []; this.pagesError = this.errText(j); }
      this.busy = false;
    },
    async choosePage() {
      if (!this.selectedPage) return;
      this.busy = true; this.pageError = '';
      const j = await this.api('/pages/select', { page_id: this.selectedPage });
      if (j.ok) {
        this.pageId = j.page_id; this.pageMask = j.page_mask; this.pageToken = ''; this.pageReady = true;
        this.setPosts(j.posts); this.counts = null; this.edgeErrors = {};
      } else this.pageError = this.errText(j);
      this.busy = false;
    },
    async savePage() {
      this.busy = true; this.pageError = '';
      const j = await this.api('/page/manual', { page_id: this.pageId, token: this.pageToken || null });
      if (j.ok) {
        this.pageMask = j.page_mask; this.pageToken = ''; this.pageReady = j.has_page_token;
        this.setPosts(j.posts); this.counts = null;
      } else this.pageError = this.errText(j);
      this.busy = false;
    },

    async loadPosts() {
      this.loading = true; this.loadError = ''; this.edgeErrors = {}; this.counts = null;
      const j = await this.api('/posts/load');
      if (j.ok) {
        this.counts = j.counts; this.edgeErrors = j.errors || {}; this.setPosts(j.posts);
        if (j.counts.total === 0) this.loadError = 'No posts found. Check Page Token permissions: pages_read_engagement + pages_manage_posts, and that the Page ID is correct.';
      } else this.loadError = this.errText(j);
      this.loading = false;
    },
    async clearList() {
      if (!confirm('Clear the collected list? This does not delete anything on Facebook.')) return;
      await this.api('/posts/clear'); this.setPosts([]); this.counts = null; this.failedIds = [];
    },

    // ---- delete run ----
    percent() { const p = this.stats.done + this.stats.failed; return this.stats.total ? Math.round(p / this.stats.total * 100) : 0; },
    remaining() { return Math.max(0, this.stats.total - this.stats.done - this.stats.failed); },

    removeSelected() {
      const ids = this.posts.filter(p => this.selected[p.id]).map(p => p.id);
      if (!confirm('Permanently delete up to ' + (this.howMany > 0 ? Math.min(this.howMany, ids.length) : ids.length) + ' posts from Facebook? This cannot be undone.')) return;
      this.run(ids);
    },
    removeAll() {
      if (this.confirmText !== 'REMOVE-ALL') return;
      const n = this.howMany > 0 ? Math.min(this.howMany, this.posts.length) : this.posts.length;
      if (!confirm('Permanently delete ' + n + ' posts from Facebook? This cannot be undone.')) return;
      this.run(this.posts.map(p => p.id));
    },
    retryFailed() { if (this.failedIds.length) this.run([...this.failedIds], true); },
    stop() { this.stopped = true; this.status = 'Stopping... finishing in-flight deletes.'; },

    async run(ids, isRetry = false) {
      if (this.running || !ids.length) return;
      const cap = parseInt(this.howMany) || 0;
      if (cap > 0 && !isRetry) ids = ids.slice(0, cap);
      const workers = clamp(this.workers, 1, 10), delayMs = clamp(this.delay, 0, 30) * 1000;

      this.running = true; this.stopped = false; this.rateLimited = false; this.lastError = '';
      this.failedIds = []; this.stats = { total: ids.length, done: 0, failed: 0 };
      const queue = [...ids];
      this.status = `Removing 0 / ${ids.length}...`;

      const worker = async () => {
        while (!this.stopped && queue.length) {
          const id = queue.shift();
          const r = await this.api('/posts/delete', { id });
          if (r.ok) {
            this.stats.done++;
            this.posts = this.posts.filter(p => p.id !== id); delete this.selected[id];
          } else {
            this.stats.failed++; this.failedIds.push(id); this.lastError = this.errText(r);
            if (r.rate_limited) { this.rateLimited = true; this.stopped = true; }
          }
          if (!this.stopped) this.status = `Removing ${this.stats.done + this.stats.failed} / ${this.stats.total}...`;
          if (delayMs && !this.stopped && queue.length) await sleep(delayMs);
        }
      };
      await Promise.all(Array.from({ length: workers }, worker));

      const { done, failed } = this.stats, left = queue.length;
      if (this.rateLimited) this.status = 'STOPPED: rate-limit. Wait, then run the rest again.';
      else if (this.stopped) this.status = `Stopped. ${done} removed, ${left} remaining, ${failed} failed — resume with Remove Selected / Retry Failed Only.`;
      else if (failed) this.status = `Finished. ${done} removed, ${failed} failed — tap Retry Failed Only.`;
      else this.status = `Finished. All ${done} posts removed.`;
      this.stats.total = this.stats.done + this.stats.failed + left; // keep Remaining accurate after a stop
      this.running = false; this.stopped = false; this.confirmText = '';
    },
  };
}
</script>
</body>
</html>
