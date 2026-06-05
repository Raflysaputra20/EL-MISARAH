# AGENTS.md

## Project overview
- This repository is a PHP + MySQL web app for a boarding house / kost management system.
- The public entry point is `index.php`. It routes guest pages and redirects admin/penghuni roles to the appropriate backend area.
- Core application logic lives in `backend/`:
  - `backend/api/` for login, registration, and payment flows
  - `backend/admin/` for admin management screens
  - `backend/penghuni/` for tenant-facing pages
- UI fragments and page layouts are in `frontend/`.

## Important conventions
- Keep changes compatible with the existing plain-PHP approach. Do not introduce a framework or dependency manager unless explicitly requested.
- Most pages start with `session_start();` and then require `backend/config/database.php`.
- Use PDO and the existing `$conn` connection variable; avoid rewriting the DB layer.
- Many tenant pages rely on `backend/penghuni/init.php`, which auto-creates missing tables/columns. If you change schema assumptions, update that file and related migration helpers in `scratch/` when needed.
- Follow the current redirect style: unauthorized access should redirect to `backend/api/auth/login.php` or the public modal flow in `index.php`.

## Key files to inspect first
- `index.php` — main router and role-based redirect logic
- `backend/config/database.php` — DB connection settings
- `backend/penghuni/init.php` — schema bootstrap and helper functions
- `backend/api/auth/login.php` — authentication and role handling
- `frontend/layouts/main.php` — shared public layout

## Development notes
- The local database is expected to be a MySQL instance available through Laragon/XAMPP, with DB name `kost_elmisarah_main`, user `root`, and empty password.
- The project includes diagnostic scripts under `scratch/` for schema and data repair; use them as references for database-related fixes, not as the primary app logic.
- There is no formal automated test suite in the repository. Verify PHP changes with syntax checks and by exercising the affected page/flow in the browser.

## Agent guidance
- Prefer small, targeted edits that match existing file structure and naming.
- When adding or changing database behavior, update the relevant page and the bootstrap/migration code together.
- Preserve existing Indonesian language strings and UI wording unless the task explicitly asks for a translation change.
