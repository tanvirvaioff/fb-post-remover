# FB Page Post Remover (JioPaglaLtd)

Private admin tool: delete posts from your own Facebook Page via the Meta Graph API (v21.0). One user, one Page at a time.

## Requirements
- PHP 8.2+, Composer, SQLite or MySQL
- A fresh Laravel project (latest stable)

## Install
1. Create the project: `composer create-project laravel/laravel fb-post-remover`
2. Copy this folder's contents over it, keeping paths: `routes/web.php` (replace), `app/`, `database/migrations/`, `resources/views/dashboard.blade.php`.
3. In `.env` set:
   ```
   APP_DEBUG=false
   SESSION_DRIVER=database
   SESSION_ENCRYPT=true
   LOG_LEVEL=error
   ```
   (SQLite works out of the box with the default Laravel `.env`.)
4. `php artisan migrate`
5. `php artisan serve` and open http://127.0.0.1:8000

The UI loads Tailwind and Alpine.js from CDNs, so no npm build is needed.

## Get a token
Option A: Graph API Explorer User Token with `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `pages_manage_metadata`.

Option B: Business Settings > System Users > Add > Assign Page with Full Control > Installed Apps > Add App with Admin role > Generate Token.

Paste it as the User Access Token, click Load My Pages, choose your Page. Or enter YOUR_PAGE_ID and YOUR_TOKEN (a Page Access Token) manually.

## How it works
- Tokens stay in the encrypted server session only. They are never written to the database, logs, CSV, or page source (only a masked preview is shown). Log out clears them.
- Load Posts reads `/posts`, `/feed` and `/published_posts` (limit 100, up to 3 pages each), merges and de-duplicates by post ID, and shows per-edge counts. The list is saved in the `collected_posts` table, so a refresh keeps it. Deleted posts are removed from the list.
- Delete runs from the browser through this app's server (`POST /posts/delete`), one post per call, with your delay. Workers (1 to 5) share one queue. Stop lets in-flight deletes finish and keeps the rest selectable. A failed post goes to Retry Failed Only. Error code 368 or "rate"/"limit" stops the run.
- Remove All needs the text REMOVE-ALL plus a confirm dialog. Deleting is permanent.
- Only posts in the collected list for the current Page can be deleted.

## Testing checklist
1. Load Pages with a valid User Token returns your Pages; selecting one fills Page ID and Page token.
2. Load Posts returns posts from all three edges, de-duplicated, with per-edge counts, and the list survives a refresh.
3. Remove Selected on 1-2 posts returns Done and those posts are gone from Facebook.
4. Remove All needs REMOVE-ALL and the confirm dialog, otherwise nothing is deleted.
5. During a run, buttons are disabled, the progress bar and Done/Remaining/Failed update live, and no per-item log is shown.
6. A failed post (transient error) counts as Failed, not Done, and Retry Failed Only retries only that post.
7. No token appears in the page source, logs, database, or CSV export after pasting.
8. The UI is English only, JioPaglaLtd branded, and looks professional on desktop.
