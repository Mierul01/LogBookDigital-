# Digital LogBook+

A weekly project logbook for UKM students and their supervisors, built with **Laravel 12**, Blade, and Tailwind CSS 4.

- **Students** write one logbook entry per week: progress, current status, problems, and next week's task.
- **Supervisors** register their students, then read each entry, add a comment, and sign it on a signature pad.
- Once an entry is signed it's locked, so the student can no longer edit it.

## Running it locally

You need PHP 8.2+, Composer, and Node.js 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Then open http://127.0.0.1:8000.

By default it uses SQLite (`database/database.sqlite`). To use MySQL instead, set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`, then run `php artisan migrate --seed` again.

While you're changing the UI, run `npm run dev` alongside `php artisan serve` so CSS and JS reload as you edit.

### Demo accounts (from the seeder)

| Role       | Username    | Password   |
|------------|-------------|------------|
| Supervisor | `dr.aisyah` | `password` |
| Student    | `A192910`   | `password` |
| Student    | `A192911`   | `password` |

## Tests

```bash
php artisan test
```

## Project structure

| Path | What's in it |
|------|--------------|
| `app/Models/User.php` | Students and supervisors share one table, split by `role`. A student belongs to a supervisor through `supervisor_id`. |
| `app/Models/Logbook.php` | One weekly entry. `reviewed_at` is set when the supervisor signs it. |
| `app/Policies/LogbookPolicy.php` | Who can view, edit, delete, and review each entry. |
| `app/Http/Controllers/` | `LogbookController`, `StudentController` (supervisors only), `ProfileController`, `DashboardController`, `Auth/*` |
| `app/Http/Requests/` | Form validation, including the one-entry-per-week rule. |
| `resources/views/` | Blade views. The layouts and shared UI pieces are in `components/`. |
| `resources/js/app.js` | Signature pad, delete confirmations, and the mobile menu. |

## Changes from the original PHP version

- Passwords are hashed with bcrypt instead of unsalted SHA-1, and the student list no longer shows password hashes.
- Every form has CSRF protection, and deletes use `DELETE` requests instead of GET links.
- Access checks now happen on the server for every action. For example, a supervisor can only see and sign their own students' entries.
- Blade escapes every value it prints, which closes the XSS holes in the old pages.
- Anyone could self-register as a supervisor before, and that's still true. Students are still added by their supervisor.
