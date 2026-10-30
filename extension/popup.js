var items = [];
var pagesMap = {};
var failedItems = [];
var running = false;
var stopped = false;
function setStatus(t) { document.getElementById('status').textContent = t; }
function showSpinner(on) { document.getElementById('spinner').style.display = on ? 'block' : 'none'; }
function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
function setStats(total, done, failed) {
  document.getElementById('statTotal').textContent = total;
  document.getElementById('statDone').textContent = done;
  document.getElementById('statLeft').textContent = total - done - failed;
  document.getElementById('statFailed').textContent = failed;
  document.getElementById('progressWrap').style.display = total ? 'block' : 'none';
  document.getElementById('progressBar').style.width = total ? Math.round(((done + failed) / total) * 100) + '%' : '0%';
  document.getElementById('progressText').textContent = total ? ('Done: ' + done + ' / ' + total + '  |  Remaining: ' + (total - done - failed) + '  |  Failed: ' + failed) : '';
  document.getElementById('retryFailed').style.display = failed ? 'block' : 'none';
}
function render() {
  var list = document.getElementById('list'); list.innerHTML = '';
  items.forEach(function (it, idx) {
    var div = document.createElement('div');
    var cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = true; cb.dataset.idx = idx;
    var span = document.createElement('span');
    span.textContent = ' ' + (it.created_time || '').slice(0, 10) + ' — ' + (it.message || '(no text)').slice(0, 80);
    div.appendChild(cb); div.appendChild(span); list.appendChild(div);
  });
  setStatus(items.length + ' posts found. Uncheck any you want to keep.');
  setStats(items.length, 0, 0);
}
function checkedItems() {
  var out = [];
  document.querySelectorAll('#list input[type=checkbox]').forEach(function (cb) { if (cb.checked) out.push(items[parseInt(cb.dataset.idx, 10)]); });
  return out;
}
function saveState() {
  try {
    if (chrome && chrome.storage && chrome.storage.local) {
      chrome.storage.local.set({savedPosts: items, savedPageId: document.getElementById('pageId').value});
    }
  } catch (e) {}
}
function restoreState() {
  try {
    if (chrome && chrome.storage && chrome.storage.local) {
      chrome.storage.local.get(['savedPosts', 'savedPageId'], function (res) {
        if (res && res.savedPosts && res.savedPosts.length) {
          items = res.savedPosts;
          if (res.savedPageId) document.getElementById('pageId').value = res.savedPageId;
          render();
          setStatus(items.length + ' posts restored from saved list (token not saved — enter token again to remove).');
        }
      });
    }
  } catch (e) {}
}
document.getElementById('openTab').addEventListener('click', function () {
  if (chrome && chrome.tabs) chrome.tabs.create({url: chrome.runtime.getURL('popup.html')});
  else window.open('popup.html', '_blank');
});
restoreState();
function setRunning(on) {
  running = on;
  ['loadPages', 'collect', 'removeSel', 'removeAll'].forEach(function (id) { document.getElementById(id).disabled = on; });
  document.getElementById('stopBtn').disabled = !on;
}
document.getElementById('stopBtn').addEventListener('click', function () {
  stopped = true;
  setStatus('Stopping... current deletes will finish, remaining will stay.');
});
document.getElementById('loadPages').addEventListener('click', async function () {
  var ut = document.getElementById('userToken').value.trim();
  if (!ut) { alert('Enter your User Access Token first.'); return; }
  setStatus('Loading Pages...'); showSpinner(true);
  try {
    var r = await fetch('https://graph.facebook.com/v21.0/me/accounts?fields=id,name,access_token', {headers: {'Authorization': 'Bearer ' + ut}});
    var data = await r.json();
    if (!r.ok) { setStatus('Load Pages failed. Check token / permissions.'); showSpinner(false); return; }
    var pages = data.data || [];
    if (!pages.length) { setStatus('No Pages found.'); showSpinner(false); return; }
    pagesMap = {}; var sel = document.getElementById('pageSelect'); sel.innerHTML = '';
    pages.forEach(function (pg) {
      var label = pg.name + ' (' + pg.id + ')'; pagesMap[label] = pg;
      var opt = document.createElement('option'); opt.textContent = label; sel.appendChild(opt);
    });
    document.getElementById('pageId').value = pages[0].id;
    document.getElementById('pageToken').value = pages[0].access_token || '';
    setStatus(pages.length + ' Pages found.');
  } catch (e) { setStatus('Load Pages failed: ' + e); }
  showSpinner(false);
});
document.getElementById('pageSelect').addEventListener('change', function () {
  var pg = pagesMap[this.value];
  if (pg) { document.getElementById('pageId').value = pg.id; document.getElementById('pageToken').value = pg.access_token || ''; }
});
document.getElementById('checkAll').addEventListener('click', function () { document.querySelectorAll('#list input[type=checkbox]').forEach(function (c) { c.checked = true; }); });
document.getElementById('uncheckAll').addEventListener('click', function () { document.querySelectorAll('#list input[type=checkbox]').forEach(function (c) { c.checked = false; }); });
document.getElementById('exportCsv').addEventListener('click', function () {
  var csv = 'id,created_time,message\n' + items.map(function (it) { return it.id + ',' + (it.created_time || '') + ',"' + (it.message || '').replace(/"/g, '') + '"'; }).join('\n');
  var blob = new Blob([csv], {type: 'text/csv'}); var a = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = 'posts.csv'; a.click();
});
document.getElementById('collect').addEventListener('click', async function () {
  if (running) return;
  var pageId = document.getElementById('pageId').value.trim();
  var token = document.getElementById('pageToken').value.trim();
  if (!pageId || !token) { alert('Select a Page / enter a Page Token first.'); return; }
  setStatus('Detecting posts...'); showSpinner(true); setRunning(true);
  items = []; failedItems = [];
  var seen = {};
  try {
    var edges = ['posts', 'feed', 'published_posts'];
    for (var ei = 0; ei < edges.length; ei++) {
      var edge = edges[ei]; setStatus('Detecting posts via ' + edge + '...');
      var url = 'https://graph.facebook.com/v21.0/' + pageId + '/' + edge + '?fields=id,message,created_time&limit=100';
      for (var p = 0; p < 3; p++) {
        var r = await fetch(url, {headers: {'Authorization': 'Bearer ' + token}});
        var data = await r.json();
        if (!r.ok) break;
        (data.data || []).forEach(function (it) { if (!seen[it.id]) { seen[it.id] = true; items.push(it); } });
        if (data.paging && data.paging.next) url = data.paging.next; else break;
      }
    }
    if (!items.length) setStatus('No posts found. Check Page Token permissions: pages_read_engagement + pages_manage_posts.');
    else { render(); saveState(); }
  } catch (e) { setStatus('Collect failed: ' + e); }
  showSpinner(false); setRunning(false);
});
async function run(targets) {
  if (running) return;
  var token = document.getElementById('pageToken').value.trim();
  var delay = parseInt(document.getElementById('delay').value || '1', 10);
  var workers = parseInt(document.getElementById('workers').value || '1', 10);
  if (workers < 1) workers = 1; if (workers > 10) workers = 10;
  var count = parseInt(document.getElementById('count').value || '0', 10);
  if (count > 0) targets = targets.slice(0, count);
  if (!targets.length) { alert('No posts selected.'); return; }
  if (!token) { alert('Page Access Token is required.'); return; }
  setRunning(true); stopped = false; failedItems = [];
  var done = 0, failed = 0, next = 0, finished = 0;
  setStats(targets.length, 0, 0);
  async function worker() {
    while (!stopped && next < targets.length) {
      var i = next++; var it = targets[i]; var ok = false;
      setStatus('Removing with ' + workers + ' worker(s)... ' + (finished + 1) + ' / ' + targets.length);
      try {
        var r = await fetch('https://graph.facebook.com/v21.0/' + encodeURIComponent(it.id), {method: 'DELETE', headers: {'Authorization': 'Bearer ' + token}});
        ok = r.ok;
      } catch (e) { ok = false; }
      if (ok) done++; else { failed++; failedItems.push(it); }
      finished++;
      setStats(targets.length, done, failed);
      if (!stopped && delay > 0) await sleep(delay * 1000);
    }
  }
  var jobs = [];
  for (var w = 0; w < workers; w++) jobs.push(worker());
  await Promise.all(jobs);
  if (stopped) setStatus('Stopped. ' + done + ' removed, ' + (targets.length - done - failed) + ' remaining, ' + failed + ' failed — resume with Remove Selected / Retry Failed Only.');
  else setStatus(failed ? ('Finished. ' + done + ' removed, ' + failed + ' failed — tap Retry Failed Only.') : ('Finished. All ' + done + ' posts removed.'));
  setRunning(false);
}
document.getElementById('removeSel').addEventListener('click', function () { run(checkedItems()); });
document.getElementById('removeAll').addEventListener('click', function () {
  if (document.getElementById('confirm').value.trim() !== 'REMOVE-ALL') { alert('For Remove All, type REMOVE-ALL first.'); return; }
  if (!confirm('Delete all collected posts? Are you sure?')) return;
  run(items);
});
document.getElementById('retryFailed').addEventListener('click', function () { run(failedItems.slice()); });
