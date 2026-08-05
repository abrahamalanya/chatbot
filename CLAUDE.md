# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Laravel 12 app for **CREDIMAS**: a WhatsApp chatbot + advisor dashboard for loan lead intake. Clients message a WhatsApp Business number, a bot walks them through a menu (mortgage/vehicle/daily credit, business/employment info, loan amount), then hands them off to a human advisor through a queue/assignment system. Advisors work leads from a Blade+Alpine chat panel. There's also a public standalone lead-capture form (not tied to WhatsApp).

Everything is in Spanish (model fields, routes, UI strings) — match that when adding features.

## Commands

- `composer dev` — runs the full local stack concurrently: `php artisan serve`, `php artisan queue:listen`, and `npm run dev` (Vite).
- `npm run dev` / `npm run build` — Vite only (Tailwind + Alpine assets).
- `composer test` or `php artisan test` — run the test suite (clears config cache first).
- Single test: `php artisan test --filter=TestName` or `php artisan test tests/Feature/Auth/AuthenticationTest.php`.
- `php artisan migrate` — run migrations (SQLite db at `database/database.sqlite`).
- `php artisan db:seed --class=RolesAndUsersSeeder` — creates the four roles (`sistema`, `admin`, `asesor`, `supervisor`) and a `sistema` superuser.
- `vendor/bin/pint` — code style (Laravel Pint).
- `php artisan pail` — tail logs.

### Testing the WhatsApp webhook locally

Meta's webhook requires a public HTTPS URL, so local dev needs a tunnel:

```
php -S 127.0.0.1:8001 -t public
ngrok http 8001
```

Or, when serving via Laravel Herd (`chatbot.test`):

```
ngrok http --host-header=chatbot.test http://127.0.0.1:80
```

Set the ngrok URL + `VERIFY_TOKEN` (from `.env`) in the Meta app's webhook config.

## Architecture

### WhatsApp message flow

`WebhookController` (routes `GET/POST /webhook`, CSRF-exempt) is the single entry point for all inbound WhatsApp traffic. `POST /webhook` extracts the first message from Meta's payload and hands it to `ChatbotService::handle()`, which is the core state machine:

1. Look up the client's most recent non-closed `Assignment` by phone number (`cliente_telefono`).
2. If that assignment has an **active conversation window** (`isConversationActive()` — assigned, accepted, and `conversation_expires_at` in the future), the message is just saved and relayed to the advisor's chat panel — the bot does not intercept.
3. Otherwise the message is routed through the bot's button-driven menu tree (interactive WhatsApp button replies, ids like `credito_hipotecario`, `negocio_true`, `prestamo_1000_mas`, etc. — see `OPCIONES_LABELS` in `ChatbotService`), which walks the client through credit type → business/employment → housing → loan amount, then calls `AssignmentService::requestAdvisor()`.
4. All inbound content (text, button replies, images/docs/video/audio/location) is persisted as `Message` rows regardless of bot vs. human mode, so the advisor always sees full history.

### Assignment / queue lifecycle (`app/Models/Assignment.php`, `app/Services/AssignmentService.php`)

States: `pending` → `assigned` → `closed`. Assigning an advisor does **not** start the conversation clock — `accepted_at`/`conversation_expires_at` are only set when the advisor explicitly accepts (`ChatController::accept` → `AssignmentService::acceptAssignment`). Advisors can `extend` the window; conversations auto-expire and get lazily closed (`disposition = tiempo_expirado`) the next time the advisor's chat view loads that client.

`php artisan assignments:check-waiting` (scheduled every minute in `routes/console.php`) nudges clients still in `pending`: first warning at 5 min, reminders every 10 min via `sendEsperaOpciones()` (seguir esperando / dejar mensaje / cancelar), and auto-closes with `disposition = sin_respuesta` after 20 min — unless the client already left a written note (`nota_dejada`), which protects the lead from being dropped.

### Roles & access (Spatie Permission)

Four roles, enforced via route-group middleware in `routes/web.php` (`role:sistema|admin`, `role:asesor|supervisor`, combined for shared routes):

- `sistema` / `admin` — dashboard, advisor CRUD, user CRUD, all-clients view, manual assignment.
- `asesor` / `supervisor` — the chat panel (`/chat`), only their own assigned clients.

`bootstrap/app.php` catches `Spatie\Permission\Exceptions\UnauthorizedException` and redirects asesor/supervisor users to `chat.index` instead of showing a 403 — keep that in mind when adding new role-gated routes.

### Key models

- `Assignment` — one row per client↔advisor pairing/session (not one row per client). A client can have many assignments over time; always query "latest" per `cliente_telefono`.
- `Message` — unified inbound/outbound log (`sender`: cliente/asesor, `tipo`: texto/opcion/imagen/documento/video/audio/ubicacion), with media stored via `WhatsappService::downloadMedia()` on the `public` disk.
- `Cliente` — separate "registered client" record (name, stage/`etapa`) that advisors fill in manually; distinct from the raw `cliente_telefono`-keyed chat/assignment data.
- `Advisor` — linked 1:1 to a `User` (`advisor_id` on `users`).

### Outbound WhatsApp (`app/Services/WhatsappService.php`, `ChatbotService`)

Both call the Graph API directly (`https://graph.facebook.com/v25.0/{phone_number_id}/messages`) via `Http::withToken()`; there's no SDK wrapper. `WhatsappService` handles free-form text/template messages and media download; `ChatbotService` owns all the interactive-button menu messages (the actual bot script text lives in `config/messages.php`, and credit requirement copy is in `config('messages.creditos.*')`). Template names (`asesor_asignado`, `asesor_acepto`) must match approved Meta templates — configurable via env.

### Frontend

Blade + Alpine.js + Tailwind, no SPA framework. `ChatController::messages` is polled as a JSON endpoint (`/chat/messages`) for near-real-time updates in the chat panel rather than websockets.
