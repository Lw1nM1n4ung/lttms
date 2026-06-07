# ✈ LTTMS — Local Tourism & Travel Management System

A three-tier PHP web application connecting tourists, travel agents, and administrators in Myanmar. Browse tour packages, book trips with KBZ Pay verification, manage hotels/transport, and handle user approvals with NRC document verification.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Runtime | PHP 8.2 + Apache |
| Database | MySQL 8.0 (InnoDB, real prepared statements) |
| Frontend | Vanilla JS + CSS (Cormorant Garamond / Outfit fonts) |
| Auth | Session-based with CSRF tokens, hardened cookies |
| i18n | Bilingual English / Burmese (မြန်မာ) via flat array system |

No framework — plain PHP with manual `require_once` includes.

## Quick Start

### Docker (recommended)

```bash
docker compose up -d --build    # http://localhost:8888
docker compose down -v          # Stop + destroy DB
```

### XAMPP (Windows)

```powershell
.\setup.ps1                     # Auto-downloads XAMPP if missing, creates DB, seeds data
```

### PHP Built-in Server

```bash
php -S localhost:8000           # Then open http://localhost:8000/setup.php
```

Run `setup.php` in the browser to initialize the database. Docker auto-loads `sql/schema.sql` on first start.

## Demo Accounts

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Agent | `agent1` | `password` |
| Customer | Register at `/register.php` | — |

## Project Structure

```
lttms/
├── config/database.php      # PDO singleton (env-configured)
├── includes/
│   ├── auth.php             # Sessions, CSRF, role guards
│   ├── functions.php        # Helpers, upload handlers, DB queries
│   ├── lang.php             # t() translation loader
│   ├── header.php           # HTML head + navbar
│   └── footer.php           # Scripts + close HTML
├── customer/                # Package browsing, booking, my-bookings
├── agent/                   # Dashboard, packages, bookings, settings
├── admin/                   # Dashboard, users (NRC verify), packages, destinations
├── lang/                    # en.php / mm.php translation files
├── sql/schema.sql           # Full schema + seed data
├── assets/                  # CSS / JS
├── uploads/                 # packages/ nrc/ destinations/
├── setup.php                # Browser-based DB installer
├── setup.ps1                # Windows one-step XAMPP installer
├── docker-compose.yml
└── .htaccess                # Apache rewrite + security headers
```

## Role System

| Role | Access |
|------|--------|
| `customer` | Browse packages, book trips, view own bookings |
| `agent` | CRUD packages/hotels/transport, manage bookings, KBZ Pay settings |
| `admin` | Approve/suspend users, view NRC documents, manage all packages, destinations, reports |

All new accounts default to `status = 'pending'` and require admin approval before login.

## Security

- **CSRF** — every POST form requires a per-session token verified with `hash_equals()`
- **Session hardening** — `httponly`, `samesite=Lax`, `strict_mode`, regeneration on login
- **XSS** — all user output through `sanitize()` (wraps `htmlspecialchars(ENT_QUOTES)`)
- **Uploads** — MIME validated via `getimagesize()`, randomized filenames, `.htaccess` blocks PHP execution
- **Passwords** — `password_hash(PASSWORD_DEFAULT)` / `password_verify()`
- **SQL** — PDO real prepared statements (`ATTR_EMULATE_PREPARES = false`)

## Booking Flow

1. Customer selects package → enters number of people
2. Transfers total via KBZ Pay → enters transaction reference
3. Agent verifies payment → confirms booking
4. Status visible to both parties in real-time

## License

Educational project — Myanmar tourism domain.
