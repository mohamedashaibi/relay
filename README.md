# Relay

**https://relay.tanami.ly** · contact: info@tanami.ly · [MIT License](LICENSE)

Built by [Mohamed Ashaibi](https://ashaibi.com).

AI-first task management. Projects, a kanban board, a review workflow, and an MCP server so Claude, ChatGPT, or any MCP client can fetch your tasks, update statuses, and hand work back for review.

**Stack:** Laravel 13 · Passport (OAuth + personal tokens) · laravel/mcp · Vue 3 · Tailwind 4 · SQLite by default.

## Run it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan passport:keys
php artisan migrate --seed      # demo data: demo@relay.test / password1234
npm run build                   # or: npm run dev
php artisan serve               # http://127.0.0.1:8000
```

## Homepage

Guests visiting `/` get the public homepage; signed-in users get their dashboard. Language versions: `/en` and `/ar` (Arabic is right-to-left). The text lives in `lang/en/home.php` and `lang/ar/home.php`, and a test checks the two files have the same keys. The contact email comes from `RELAY_CONTACT_EMAIL`.

For production, `.env.example` is set up for `https://relay.tanami.ly` (`APP_ENV=production`, `APP_DEBUG=false`). Copy it to `.env` on the server and fill in the database, mail, and `APP_KEY` values.

## Email

**What gets sent:**

| Email | Sent to |
| --- | --- |
| Task assigned | The new assignee |
| Status changed | The assignee and the task's creator |
| Review requested | The named reviewer, or the project's owners and admins if no reviewer is named |
| Approved / changes requested | The assignee and the task's creator |
| New comment | The assignee, creator and reviewer |
| Added to a project | The new member |
| Welcome, password reset, password changed | The account holder (these always send) |

Nobody is emailed about their own actions, including changes their AI assistant makes for them. When an assistant does act, emails to other people say so (for example "Ada (via Claude)"). Each person can switch every task email type on or off under **Account & email settings**.

**Setup:**
- **Mail server:** set the `MAIL_*` variables (see `.env.example`), then check them with `php artisan relay:test-mail you@example.com`. Locally, `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`.
- **Queue worker:** emails are queued, so the server needs one running all the time. A Supervisor example:

  ```ini
  [program:relay-queue]
  command=php /var/www/relay/artisan queue:work --tries=3 --backoff=10 --max-time=3600
  autostart=true
  autorestart=true
  user=www-data
  ```

  Restart it after each deploy with `php artisan queue:restart`.

**Password reset:** reset links expire after 60 minutes and work once. A completed reset signs the account out of all browser sessions. AI connections keep working, and the "password changed" email points people to the AI connections page in case the change wasn't them.

## Connect an AI

The MCP endpoint is `{APP_URL}/mcp`. The **AI connections** page in the app has copy-paste setup for each client.

| Client | Auth |
| --- | --- |
| Claude.ai / Claude Desktop | Settings → Connectors → Add custom connector → paste URL. OAuth consent screen. |
| ChatGPT | Settings → Apps & Connectors → Developer mode → Create, OAuth. |
| Claude Code | `claude mcp add --transport http relay {APP_URL}/mcp --header "Authorization: Bearer <token>"` |
| Cursor / others | URL + personal token, or OAuth if the client supports it. |

Claude.ai and ChatGPT reach your server from the internet. Run it on a public HTTPS URL (deploy it, or use a tunnel like `herd share` or ngrok) and set `APP_URL` to that address.

### MCP tools

`get_my_work` · `list_projects` · `get_project` · `search_tasks` · `get_task` · `create_task` · `update_task` · `set_task_status` · `request_review` · `review_task` · `add_comment` · `read_attachment` · `attach_file`

## Attachments

Files can be attached to a task (in the task panel) or to a project (the **Files** button on the board), to give people and assistants more context.

- **Accepted:** images (PNG, JPG, GIF, WebP), PDF, TXT, Markdown, CSV, JSON, XML, YAML, and Word, Excel and PowerPoint files. SVG and HTML are refused because they can carry scripts. The file's contents must match its extension.
- **What assistants get:** `get_task` and `get_project` list the files, and `read_attachment` returns the text, split into pages for long files. Text is extracted once at upload from text files (including older Arabic files in Windows-1256), PDFs, Word and PowerPoint. Images go to the assistant as images (up to 5 MB). Spreadsheets have no text preview, so export them as CSV.
- **Files from assistants:** `attach_file` lets an assistant save a text file it wrote (md, txt, csv, json, xml, yaml or log, up to 1 MB) on a task or project. It's stored like any upload, marked "via Claude" in the UI, and can be read back later with `read_attachment`.
- **Storage:** files go on a private disk (`ATTACHMENTS_DISK`, default `local` = `storage/app/private`) under random names. Downloads always need a project member's session and are sent as downloads with `nosniff`; only raster images can be shown inline. Deleting a task or project also deletes its files.
- **Size limit:** `ATTACHMENTS_MAX_KB` (default 20 MB). PHP and your web server must allow at least that much:
  - `php.ini`: `upload_max_filesize = 25M`, `post_max_size = 26M`
  - nginx: `client_max_body_size 26m;`

  If PHP's limits are lower, the app shows the lower one as the maximum.

An assistant acts as the user who connected it. Every change it makes is logged with `channel = mcp` and the client's name, and the UI shows it as "via Claude".

## Review rules

- `request_review` moves a task to **Needs review**, stores a hand-off summary, and can name a reviewer.
- Closing a task that is in review counts as an approval. Only the named reviewer or a project owner/admin can do it, and an assistant can't approve its own user's work when someone else is the reviewer.
- If you request changes, the task goes back to **In progress** and you must leave feedback.

## Architecture

```
app/Domain/          enums, read models, repository interfaces, review policy (no framework code)
app/Application/     one use case per action + DTOs; object-level access checks (ProjectAccess, TaskLocator)
app/Infrastructure/  Eloquent repositories, Passport token gateway
app/Http/            thin controllers, strict whitelist form requests, project.member middleware
app/Mcp/             MCP server + tools; they call the same use cases as the HTTP API
resources/js/        Vue SPA (session auth + CSRF)
```

Tests: `php artisan test` covers the HTTP API, access control, review gating, MCP tools, and a real bearer-token call to `/mcp`.

## Author

**Mohamed Ashaibi**

- Website: [ashaibi.com](https://ashaibi.com)
- Email: [mohamed@ashaibi.com](mailto:mohamed@ashaibi.com)
- GitHub: [@mohamedashaibi](https://github.com/mohamedashaibi)

## License

Relay is open-source software licensed under the [MIT License](LICENSE). Copyright © 2026 Mohamed Ashaibi.
