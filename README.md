# FB Page Post Remover — JioPaglaLtd

**Extension name: FB Page Post Remover (v1.3)**

**100% FREE — Open source, free forever.**

Chrome Extension + Laravel version both included.

Get it free here: https://github.com/tanvirvaioff/fb-post-remover

Private admin tool: delete posts from your own Facebook Page via the Meta Graph API (v21.0). One user, one Page at a time. Open source (MIT).

## What it does
- Load Pages: GET /me/accounts
- Load Posts: GET /{page-id}/posts + /feed + /published_posts, de-duplicated by ID, per-edge counts
- Delete: DELETE /{post-id}, Workers 1-10, Stop, Progress (Total / Done / Remaining / Failed), Retry Failed Only
- Safety: Remove All needs REMOVE-ALL + confirm. Deleting is permanent.
- Tokens live in the session only (Laravel) or are never saved (extension). No token is stored in the code, DB, logs, or CSV.

## Permissions needed
pages_show_list, pages_read_engagement, pages_manage_posts, pages_manage_metadata

## Tutorial — Chrome extension (Option B, easiest)
1. chrome://extensions -> Developer mode ON -> Load unpacked -> select the `extension/` folder
2. Open the extension popup, paste your User Token (see "Get a token" below)
3. Click Load My Pages, select your Page (Page Token is picked up automatically, never saved)
4. Click Load Posts — it collects from Posts + Feed + Published Posts and shows the total
5. Tick the posts you want, or use Remove All (type REMOVE-ALL to confirm)
6. Set Workers (1-10, default 1) and Delay, then click Remove
7. Watch the progress bar (Total / Done / Remaining / Failed). Use Stop anytime, and Retry Failed Only after a run.
8. Tip: after a browser refresh, re-enter your token — posts and Page ID are saved locally, token is not.

## Tutorial — Laravel web app (Option A, Black Premium UI)
1. composer create-project laravel/laravel fb-post-remover
2. Copy laravel/ contents over it, keeping paths: routes/web.php, app/, database/migrations/, resources/views/dashboard.blade.php
3. .env: APP_DEBUG=false, SESSION_DRIVER=database, SESSION_ENCRYPT=true, LOG_LEVEL=error
4. php artisan migrate
5. php artisan serve -> http://127.0.0.1:8000
6. In the dashboard: paste User Token -> Load My Pages -> select Page -> Load Posts -> Remove selected / Remove All (REMOVE-ALL + confirm). Workers 1-10, Stop, Retry Failed Only work the same as the extension.

## Get a token
Graph API Explorer User Token, or Business Settings > System Users > Add > Assign Page with Full Control > Installed Apps > Add App with Admin role > Generate Token. Paste it as the User Access Token, click Load My Pages, choose your Page.
