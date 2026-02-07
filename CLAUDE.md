# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A personal URL shortener (jer.wtf) built with PHP 8.3 and MySQL. A small, self-contained Apache/PHP application.

## Setup

- Requires Apache with `mod_rewrite` enabled
- Requires PHP 8.3+ with `mysqli` extension
- Copy `site/redirector.conf.sample` to `redirector.conf` (one level above `site/`) and fill in DB credentials and user logins
- The `.htaccess` rewrites all non-file requests to `index.php`
- Config file (`.conf`) is gitignored

## Architecture

All application code lives in `site/`.

**Request flow:** Apache rewrites all paths → `index.php` → parses the URL path as a short code → looks up the redirect in the DB → issues a 302 redirect. If no match, renders the admin UI (HTML form + jQuery tabs showing stats).

**Two core classes:**
- `UrlRedirector` — value object holding a short code / long URL pair, with validation. Handles the actual redirect via `getRedirectHeader()`. Short codes: alphanumeric + underscore, max 20 chars. Strips Facebook querystring junk from short codes.
- `UrlRedirectDb` — data access layer using raw `mysqli`. Opens/closes a connection per method call. Manages the `redirects` table (CRUD) and `redirect_log` table (usage tracking).

**API endpoints (POST, JSON responses):**
- `addEntry.php` — creates a new short URL (requires auth via `secusr`/`seckey` POST params)
- `updateEntry.php` — updates an existing short URL's destination (requires auth + ownership)
- `url_insert.php` — older version of add that returns HTML instead of JSON; likely deprecated

**DB tables** (inferred from queries):
- `redirects` — columns: `redirect_key`, `redirect_url`, `created_at`, unknown counter column, `user`
- `redirect_log` — columns: `redirect_key`, `date_used`

## Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run a single test
vendor/bin/phpunit --filter testMethodName
```

Tests live in `tests/`. Autoloading uses a classmap over `site/` (via Composer).

## Key Details

- The frontend JS hardcodes `https://jer.wtf/` as the API base URL
- HTTPS is enforced via a PHP redirect in `index.php` (not just `.htaccess`)
- Auth is plaintext username/password pairs stored in the config file, checked against POST data
- Config is loaded via `require()` of `redirector.conf` which sets a `$config` array with `db` and `security` keys
