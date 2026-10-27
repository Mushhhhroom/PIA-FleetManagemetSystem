# CodeIgniter 4 Application Starter

> **PIA-AFMS — Philippine Information Agency Automated Fleet Management System**
> Production deployment target: **Vercel** (see below) · Local dev: `php spark serve`

## Deploying to Vercel

The app ships with a Vercel entrypoint (`api/index.php`) that boots CodeIgniter 4
through the [`vercel-php`](https://github.com/vercel-community/php) runtime, plus a
`vercel.json` that rewrites every path to it. Vercel has **no bundled MySQL**, so an
external MySQL instance is required.

### 1. Provision MySQL and load the data

Vercel has **no bundled MySQL**, so an external MySQL 8-compatible host is
required (TiDB Serverless works well — free tier, TLS required, and the
bundled importer handles the encrypted handshake).

For the demo dataset, import the bundled schema + seed from this machine:

```bash
php database/import_db.php <host> <port> <user> <password> fleet_db
```

Alternatively, run all migrations against the empty instance (includes the
`sessions` table used for serverless login state):

```bash
php spark migrate --all
```

### 2. Create the Vercel project and set environment variables

> **Note:** Vercel rejects dotted variable names, so the cloud config uses
> SCREAMING_SNAKE names. `api/index.php` re-publishes them under the dotted
> keys CodeIgniter expects before the framework boots.

```bash
vercel link            # select your account/scope once
vercel env add CI_ENVIRONMENT               # -> production
vercel env add APP_BASEURL                  # -> https://<project>.vercel.app/
vercel env add DB_HOST
vercel env add DB_DATABASE                  # -> fleet_db
vercel env add DB_USERNAME
vercel env add DB_PASSWORD
vercel env add DB_PORT                      # -> 4000 for TiDB Cloud
vercel env add DB_ENCRYPT                   # -> true (TLS for cloud MySQL)
vercel env add SESSION_DRIVER               # -> CodeIgniter\Session\Handlers\DatabaseHandler
vercel env add SESSION_SAVEPATH             # -> sessions
vercel env add ENCRYPTION_KEY               # stable 32-byte base64 key
```

See [`.env.example`](.env.example) for the complete reference list (SMTP, Maps key,
etc.). CodeIgniter reads these straight from the process environment — no `.env`
file is deployed.

### 3. Deploy

```bash
vercel deploy --prod
```

### Serverless notes

- **Sessions live in MySQL** (`sessions` table) because the function filesystem is
  read-only and resets on every cold start; file sessions would log users out at
  random.
- `writable/` is redirected to the function's temp directory (`api/index.php`);
  logs/cache/debugbar work but are per-instance and non-persistent.
- **Uploaded files are ephemeral** on Vercel — persist them to Vercel Blob or an
  external store if you need durability.
- `CI_ENVIRONMENT=production` disables debug pages and the debug toolbar.

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds a composer-installable app starter.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Installation & updates

`composer create-project codeigniter4/appstarter` then `composer update` whenever
there is a new release of the framework.

When updating, check the release notes to see if there are any changes you might need to apply
to your `app` folder. The affected files can be copied or merged from
`vendor/codeigniter4/framework/app`.

## Setup

Copy `env` to `.env` and tailor for your app, specifically the baseURL
and any database settings.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Server Requirements

PHP version 8.5.12 (or 8.2+) is required (active deployment: PHP 8.5.12 on Zend Engine v4.5.12), with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library

## GitHub Issue #19: Gate Checkpoint Resolution Summary

Reference: [Issue #19 - Live camera viewfinder and high-contrast pass/fail verification](https://github.com/Mushhhhroom/Pia-s-Fleet-Management-System-Demo-/issues/19)

### Implemented Improvements
1. **Live Camera Viewfinder HUD**: Integrated `Html5Qrcode` with targeted emerald green reticle brackets, animated scan laser, and flashlight torch toggle.
2. **Tactile 6-Digit PIN Keypad**: Added on-screen 3x4 keypad with 56px touch targets, 6-cell digital visualizer, and auto-submit on 6th digit. Backend resolves 6-digit PINs, numeric IDs, serial numbers, and HMAC tokens.
3. **Instant Full-Screen Verdict Cards**: High-contrast Green PASS (checkmark, license plate, driver avatar/license, route, passengers) and Red REJECT (BLOWBAGETS safety checklist alert, expired authorization, vehicle holding instructions).
4. **Multi-Sensory Feedback**: Web Audio API synthesized chimes and device vibration (`navigator.vibrate`) for hands-free traffic clearance.
5. **Early Safety Pre-Screening**: BLOWBAGETS pre-trip checklist validation integrated directly into token verification before odometer entry.
6. **Controller Architecture**: Added `GateController` alias class extending `GateSecurityController`.

