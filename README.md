# FB Page Post Remover — JioPaglaLtd

Private admin tool: delete posts from your own Facebook Page via the Meta Graph API (v21.0). One user, one Page at a time. Open source (MIT).

## What it does
- Load Pages: GET /me/accounts
- Load Posts: GET /{page-id}/posts + /feed + /published_posts, de-duplicated by ID, per-edge counts
- Delete: DELETE /{post-id}, Workers 1-10, Stop, Progress (Total / Done / Remaining / Failed), Retry Failed Only
- Safety: Remove All needs REMOVE-ALL + confirm. Deleting is permanent.
- Tokens live in the session only (Laravel) or are never saved (extension). No token is stored in the code, DB, logs, or CSV.

## Permissions needed
pages_show_list, pages_read_engagement, pages_manage_posts, pages_manage_metadata

## Option A — Laravel web app (Black Premium UI)
1. composer create-project laravel/laravel fb-post-remover
2. Copy laravel/ contents over it, keeping paths: routes/web.php, app/, database/migrations/, resources/views/dashboard.blade.php
3. .env: APP_DEBUG=false, SESSION_DRIVER=database, SESSION_ENCRYPT=true, LOG_LEVEL=error
4. php artisan migrate
5. php artisan serve -> http://127.0.0.1:8000

## Option B — Chrome extension
1. chrome://extensions -> Developer mode -> Load unpacked -> extension/ folder
2. User Token -> Load My Pages -> select Page -> Load Posts -> Remove

## Get a token
Graph API Explorer User Token, or Business Settings > System Users > Add > Assign Page with Full Control > Installed Apps > Add App with Admin role > Generate Token. Paste it as the User Access Token, click Load My Pages, choose your Page.
