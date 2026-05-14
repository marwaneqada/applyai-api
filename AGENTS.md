# AGENTS.md - ApplyAI Backend Agent Rules

This file is the operating manual for AI coding agents working in this
repository. Read it before changing code, running migrations, adding packages, or
making architectural decisions. When generic Laravel advice conflicts with this
file or with nearby code, follow this file and the existing local pattern.

Last audited against the codebase: 2026-05-12.

---

## 1. Product And Scope

ApplyAI is a backend-only Laravel 13 API for an AI-powered resume and job
application assistant.

The API currently supports:

- Sanctum token auth: register, login, logout, and current-user profile.
- Resume PDF upload, storage, text extraction, listing, detail, and deletion.
- Queued AI resume-to-job analysis with structured results.
- Job application tracking with kanban-style statuses, card ordering, and stats.

This is a serious portfolio backend. Treat correctness, maintainability,
authorization, data integrity, and tests as product requirements.

Do not add frontend code unless the user explicitly asks for it. No Blade views,
Vue, React, Inertia, Livewire, Tailwind, Vite feature work, landing pages, or UI
assets belong in this repository's product scope.

---

## 2. Current Stack

| Area | Current Reality |
| --- | --- |
| Framework | Laravel 13.x |
| Composer PHP constraint | `^8.3` |
| Docker / CI PHP runtime | PHP 8.4 |
| Auth | Laravel Sanctum bearer tokens |
| Local app DB | Docker PostgreSQL, or local env-selected DB |
| Test DB | SQLite in-memory via `phpunit.xml` |
| Queue | Redis in local/Docker, sync in tests |
| Queue dashboard | Laravel Horizon |
| PDF parsing | `smalot/pdfparser` |
| AI SDK | `laravel/ai` |
| AI provider | Gemini by default, model `gemini-2.5-flash` |
| Tests | PHPUnit feature and unit tests |
| Formatting | Laravel Pint |
| CI | `.github/workflows/tests.yml` runs PHP 8.4 tests |

Use PHP syntax supported by PHP 8.4. Do not introduce PHP 8.5-only features.

---

## 3. Repository Reality

The app is intentionally domain-oriented, but it is not perfectly uniform yet.
Agents must preserve existing behavior while improving touched code carefully.

Current domain status:

| Domain | Status | Notes |
| --- | --- | --- |
| `Auth` | Implemented | Register, login, logout, `/api/me`; responses use `UserResource` plus token/message envelopes. |
| `Resume` | Implemented | Upload/list/show/delete; upload parses PDF immediately and records parse failure instead of failing the request. |
| `Analysis` | Implemented | Create/list/show/status; AI job writes one result row and marks failed on exceptions. No delete endpoint currently exists. |
| `Application` | Implemented | Create/list grouped by status/show/update/move/delete/stats. Uses `ApplicationStatus` enum. |

Important current quirks:

- `AGENTS.md` is currently untracked in git unless the user stages it.
- There is an empty untracked root file named `queue`; ignore it unless the user
  asks about it.
- Docker files are present and part of the current repo. Do not remove or
  rewrite Docker unless explicitly asked.
- Several older PHP files do not yet have `declare(strict_types=1);`. New PHP
  files must include it. When touching an older PHP file, add it if the edit is
  local and safe; do not churn unrelated files solely to normalize style.
- Some read-only controller methods currently perform scoped Eloquent queries.
  Mutation workflows should still go through DTOs and Actions.

---

## 4. Non-Negotiables

- Keep this backend-only unless the user explicitly changes scope.
- Never expose or hardcode secrets, API keys, tokens, passwords, or private env
  values.
- Never call the real AI provider from tests.
- Never return raw Eloquent models from controllers.
- Never use `$guarded = []`.
- Never skip ownership checks on user-owned resources.
- Never add repository classes, service-provider bindings, events/listeners,
  Swagger/OpenAPI annotations, billing, admin panels, email notifications, or
  frontend tooling unless explicitly requested.
- Never silently swallow domain failures except where the current behavior
  intentionally records failure state, such as resume parse failure or analysis
  job failure.
- Never make broad refactors while implementing a narrow feature.
- Never delete or revert user changes unless the user explicitly asks.

---

## 5. Architecture

Preferred flow for write endpoints:

```text
Route -> Controller -> Form Request -> DTO -> Action -> Model/Service/Job -> Resource -> Response
```

Layer responsibilities:

- Routes define HTTP shape only.
- Form Requests validate request input only.
- DTOs copy validated/request data into typed immutable input objects.
- Controllers coordinate HTTP concerns and call Actions for mutations.
- Actions own one business workflow.
- Services own reusable technical concerns such as PDF parsing, AI calls, and
  normalization.
- Jobs own queued execution and failure-state persistence.
- Models own relationships, fillable fields, and casts.
- Resources own API serialization.

Read-only endpoints may use scoped Eloquent queries in controllers when the
existing domain already follows that pattern. Keep these methods small, scoped to
the authenticated user, and serialized through Resources or a small documented
`data` payload.

---

## 6. Domain Layout

Business code lives under `app/Domains/{DomainName}`.

```text
app/Domains/{DomainName}/
  Actions/
  Dto/
  Enums/
  Jobs/
  Models/
  Services/
```

Only create folders that the domain actually needs.

HTTP code lives under:

```text
app/Http/Controllers/{DomainName}/
app/Http/Requests/{DomainName}/
app/Http/Resources/
```

Tests live under:

```text
tests/Feature/{DomainName}/
tests/Unit/{DomainOrConcern}/
```

---

## 7. Routes

All API routes live in `routes/api.php`. Preserve route order when a static route
could be captured by a resource parameter.

Current routes:

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/me

GET    /api/resumes
POST   /api/resumes
GET    /api/resumes/{resume}
DELETE /api/resumes/{resume}

GET    /api/analyses
POST   /api/analyses
GET    /api/analyses/{analysis}
GET    /api/analyses/{analysis}/status

GET    /api/applications/stats
GET    /api/applications
POST   /api/applications
GET    /api/applications/{application}
PUT    /api/applications/{application}
PATCH  /api/applications/{application}/move
DELETE /api/applications/{application}
```

Route rules:

- All non-register/login routes require `auth:sanctum`.
- Put `/applications/stats` before `applications/{application}` routes.
- Put `/applications/{application}/move` before the API resource declaration.
- Do not add `DELETE /api/analyses/{analysis}` unless implementing and testing
  that feature.

---

## 8. Controllers

Controllers live in `app/Http/Controllers/{DomainName}` and should stay thin.

Controller methods for mutations must:

- Type-hint a Form Request when input is accepted.
- Inject the Action by method injection.
- Build the DTO with `Dto::fromRequest($request)`.
- Call `$action->execute($dto)` or pass the already-authorized model for simple
  deletes matching existing code.
- Return a Resource, Resource response, no-content response, or small JSON
  envelope.

Controllers may:

- Use route model binding.
- Use `abort_unless(..., 403)` for ownership checks on simple show/delete/status
  methods matching current code.
- Query models for simple index/status/stats read endpoints when scoped to the
  authenticated user and kept small.

Controllers must not:

- Contain business workflows for create/update/move operations.
- Call AI, PDF, queue, or normalization services directly.
- Return raw models.
- Perform unscoped queries on user-owned resources.
- Grow large private helper sections; if a read endpoint becomes complex, move
  the workflow into an Action.

---

## 9. Actions

Actions live in `app/Domains/{DomainName}/Actions`.

An Action must:

- Be `final` unless there is a clear Laravel reason not to.
- Have one public method named `execute`.
- Use typed parameters and return types.
- Accept a DTO for request-driven create/update/move workflows.
- Own exactly one use case.
- Throw `ValidationException` for field-specific 422 domain validation.
- Use `abort(403)` or `abort_unless(..., 403)` for wrong-owner access when the
  existing endpoint expects forbidden.

An Action must not:

- Accept a raw `Request`.
- Return an HTTP `Response`.
- Know about Resources.
- Mix unrelated use cases.

Existing exception pattern:

- Invalid related resource owned by another user during create/update often
  returns 422 with validation errors.
- Direct access to another user's existing resource returns 403.

Follow nearby tests and endpoint behavior.

---

## 10. DTOs

DTOs live in `app/Domains/{DomainName}/Dto`.

DTO rules:

- Use `final readonly class` for new DTOs.
- Use promoted public readonly properties through `final readonly class`.
- Provide `fromRequest()` for request-backed DTOs.
- Pull authenticated IDs from `$request->user()->id`, not global `auth()`.
- Convert `Stringable` request values with `->toString()`.
- Use `filled()` for nullable optional strings.
- Keep DTOs pure: no queries, no validation decisions, no side effects.

Naming reality:

- Auth, Resume, and Analysis currently use `Dto` class suffix in places, such as
  `CreateAnalysisDto`.
- Application currently uses `DTO` suffix, such as `CreateApplicationDTO`.
- Match the casing already used inside the domain you are editing. Do not rename
  existing DTOs just to normalize casing unless the user asks for that refactor.

---

## 11. Models

Domain models live in `app/Domains/{DomainName}/Models`. `User` stays in
`app/Models/User.php`.

Model rules:

- Define fillable fields explicitly. Current models use Laravel attributes such
  as `#[Fillable([...])]`; prefer the nearby pattern.
- Define relationships with typed return values.
- Define enum and JSON casts in `casts()`.
- Keep business decisions out of models.
- Do not add validation rules to models.
- Do not use `$guarded = []`.

Cross-domain relationships are allowed when the data model requires them:

- `Analysis` belongs to `Resume` and `User`.
- `Application` belongs to `Analysis` and `User`.
- `Resume` belongs to `User`.

Keep cross-domain workflows in Actions or Jobs, not model methods.

---

## 12. Resources And Responses

Resources live in `app/Http/Resources`.

Response rules:

- Entity responses must use Resources.
- Resource collections should be used for lists when returning flat lists.
- Grouped `applications` responses should serialize each card through
  `ApplicationResource`.
- Small non-entity payloads may use `response()->json(['data' => ...])`, such as
  analysis status and application stats.
- Auth register/login currently return `{ "user": ..., "token": ... }`; preserve
  that response shape unless intentionally changing API behavior.
- Deletes should return `204 No Content`.
- Never return an Eloquent model or unwrapped model array from a controller.

Timestamp serialization currently uses `toISOString()` in Resources. Match the
nearby Resource style.

---

## 13. Requests And Validation

Form Requests live in `app/Http/Requests/{DomainName}`.

Rules:

- Every endpoint accepting input must use a Form Request.
- `authorize()` currently returns `true`; ownership is handled in Actions or
  controller route-model checks.
- Keep request validation structural and syntactic.
- Put ownership and cross-record checks in Actions when they depend on the
  authenticated user.
- Use enum values from enum helpers where available, such as
  `ApplicationStatus::values()`.

Do not validate inside controllers.

---

## 14. Database And Migrations

Current domain tables:

- `users`
- `personal_access_tokens`
- `resumes`
- `analyses`
- `analysis_results`
- `applications`
- Laravel queue/cache tables

Migration rules:

- Use Laravel anonymous migration classes.
- Implement `up()` and `down()`.
- Use snake_case plural table names.
- Use `{model}_id` foreign keys.
- Add indexes for common user/status/order lookups.
- Use `cascadeOnDelete()` or `nullOnDelete()` deliberately.
- Use timestamp columns.
- Do not add soft deletes unless the user explicitly asks.
- Keep migrations portable to SQLite tests. Prefer `$table->json()` over
  PostgreSQL-only column types unless the feature explicitly requires otherwise.
- For enum-like string fields, add a comment listing allowed values when useful.

Existing important constraints:

- `analysis_results.analysis_id` is unique.
- `applications.analysis_id` is nullable and `nullOnDelete()`.
- `applications` has an index on `user_id`, `status`, and `position`.

---

## 15. Auth Domain

Current files:

```text
app/Domains/Auth/Actions/LoginUserAction.php
app/Domains/Auth/Actions/RegisterUserAction.php
app/Domains/Auth/Dto/LoginUserDto.php
app/Domains/Auth/Dto/RegisterUserDto.php
app/Http/Controllers/Auth/*
app/Http/Requests/Auth/*
tests/Feature/Auth/AuthTest.php
```

Rules:

- Password hashing is handled by the `User` model cast.
- Login failure returns a 422 validation error on `email`.
- Register and login create Sanctum tokens named `api-token`.
- Logout deletes only the current access token.
- `/api/me` returns `UserResource` under Laravel's standard `data` envelope.

---

## 16. Resume Domain

Current files:

```text
app/Domains/Resume/Actions/DeleteResumeAction.php
app/Domains/Resume/Actions/UploadResumeAction.php
app/Domains/Resume/Dto/UploadResumeDto.php
app/Domains/Resume/Models/Resume.php
app/Domains/Resume/Services/PdfTextExtractor.php
app/Http/Controllers/Resume/ResumeController.php
app/Http/Requests/Resume/UploadResumeRequest.php
app/Http/Resources/ResumeResource.php
tests/Feature/Resume/ResumeTest.php
```

Behavior:

- Upload accepts only PDF files up to 5120 KB.
- Files are stored on the `local` disk under `resumes`.
- `UploadResumeAction` creates a row with `parse_status = pending`, then tries
  extraction.
- Successful parse sets `parse_status = success` and stores `extracted_text`.
- Failed parse keeps the uploaded row, sets `parse_status = failed`, and stores
  `parse_error`.
- PDFs with extracted text shorter than 100 characters fail parsing.
- If the database row cannot be created after storage, the stored file is
  deleted.
- Delete attempts to remove the stored file and still deletes the row if storage
  deletion fails.

Do not move PDF parsing into controllers. Do not make resume upload asynchronous
unless the user asks for that product change.

---

## 17. Analysis Domain

Current files:

```text
app/Domains/Analysis/Actions/CreateAnalysisAction.php
app/Domains/Analysis/Dto/AnalysisResultDto.php
app/Domains/Analysis/Dto/CreateAnalysisDto.php
app/Domains/Analysis/Enums/AnalysisStatus.php
app/Domains/Analysis/Jobs/AnalyzeResumeJob.php
app/Domains/Analysis/Models/Analysis.php
app/Domains/Analysis/Models/AnalysisResult.php
app/Domains/Analysis/Services/AnalysisResultNormalizer.php
app/Domains/Analysis/Services/ResumeAnalysisAgent.php
app/Domains/Analysis/Services/ResumeAnalysisService.php
app/Http/Controllers/Analysis/AnalysisController.php
app/Http/Requests/Analysis/CreateAnalysisRequest.php
app/Http/Resources/AnalysisResource.php
app/Http/Resources/AnalysisResultResource.php
tests/Feature/Analysis/AnalysisTest.php
tests/Unit/Ai/ResumeAnalysisServiceTest.php
tests/Unit/Analysis/AnalysisResultNormalizerTest.php
```

Statuses:

```text
pending
processing
completed
failed
```

Create behavior:

- A user can create an analysis only for their own resume.
- The resume must have `parse_status = success`.
- Invalid resume ownership or parse state returns 422 validation errors on
  `resume_id`.
- Creating an analysis dispatches `AnalyzeResumeJob`.
- The API returns the created analysis with `result = null`.

AI/job behavior:

- `AnalyzeResumeJob` loads the analysis, marks it `processing`, calls
  `ResumeAnalysisService`, upserts the single related result row, then marks it
  `completed`.
- Any exception is reported, the analysis is marked `failed`, and
  `error_message` stores the exception message.
- Jobs must be rerunnable without duplicate `analysis_results` failures.

AI service rules:

- `ResumeAnalysisService::analyze(Analysis $analysis)` is the single public
  analysis entry point.
- It must reject empty resume text and empty job description before prompting.
- It must call `ResumeAnalysisAgent` and require a `StructuredAgentResponse`.
- It must normalize through `AnalysisResultNormalizer`.
- It must not silently fall back to fake data.

Agent schema:

- Score fields are integers from 0 to 100.
- Array fields: `matched_keywords`, `missing_keywords`, `strengths`,
  `weaknesses`, `gap_analysis`, `rewritten_bullets`.
- `gap_analysis` items must contain `skill`, `severity`, and `explanation`;
  severity is one of `critical`, `important`, `nice_to_have`.
- `rewritten_bullets` items must contain `original` and `rewritten`.
- `cover_letter` is required.
- `raw_ai_response` and `model_used` are persisted by the normalizer/result DTO,
  not requested as required model output fields.

Testing AI:

- Always use `ResumeAnalysisAgent::fake(...)`.
- Always call `preventStrayPrompts()` in tests that fake AI.
- Do not use real Gemini/API credentials in tests.

Do not create a separate `Ai` domain. AI code for resume analysis belongs in
`app/Domains/Analysis/Services`.

---

## 18. Application Domain

Current files:

```text
app/Domains/Application/Actions/CreateApplicationAction.php
app/Domains/Application/Actions/DeleteApplicationAction.php
app/Domains/Application/Actions/MoveApplicationAction.php
app/Domains/Application/Actions/UpdateApplicationAction.php
app/Domains/Application/Dto/CreateApplicationDTO.php
app/Domains/Application/Dto/MoveApplicationDTO.php
app/Domains/Application/Dto/UpdateApplicationDTO.php
app/Domains/Application/Enums/ApplicationStatus.php
app/Domains/Application/Models/Application.php
app/Http/Controllers/Application/ApplicationController.php
app/Http/Requests/Application/CreateApplicationRequest.php
app/Http/Requests/Application/MoveApplicationRequest.php
app/Http/Requests/Application/UpdateApplicationRequest.php
app/Http/Resources/ApplicationResource.php
tests/Feature/Application/ApplicationTest.php
```

Statuses:

```text
saved
applied
interview
offer
rejected
```

Behavior:

- `GET /api/applications` returns a `data` object grouped by every status. Empty
  statuses return empty arrays.
- Cards are ordered by `position`.
- Creating a card defaults to `saved`.
- Creating a card sets `position` to max position in `saved` plus `1.0`.
- `analysis_id` is nullable; when present, it must belong to the current user or
  return 422 on `analysis_id`.
- Update can change application fields but not status. Status changes belong to
  the move endpoint.
- Delete returns 204.
- Stats return:
  - `total`
  - `active`, which excludes rejected applications
  - `by_status`, containing every status key

Move behavior:

- Moving validates the target status with `ApplicationStatus`.
- `after_application_id` and `before_application_id` are optional.
- Neighbor cards must belong to the authenticated user or return 403.
- Neighbor cards must already be in the requested target status or return 422 on
  the offending neighbor field.
- If both neighbors are present, new position is their average.
- If only `after` is present, new position is `after.position + 1.0`.
- If only `before` is present, new position is `before.position / 2`.
- If neither neighbor is present and status is unchanged, keep current position.
- If neither neighbor is present and status changes, append at max position in
  target status plus `1.0`.

Do not add drag/reorder concepts outside this float-position scheme unless the
user asks for a different ordering model.

---

## 19. AI Configuration

Current config lives in `config/services.php`:

```php
'ai' => [
    'resume_analysis' => [
        'provider' => env('AI_RESUME_ANALYSIS_PROVIDER', 'gemini'),
        'model' => env('AI_RESUME_ANALYSIS_MODEL', 'gemini-2.5-flash'),
        'timeout' => env('AI_RESUME_ANALYSIS_TIMEOUT', 60),
        'temperature' => env('AI_RESUME_ANALYSIS_TEMPERATURE', 0.1),
    ],
],
```

Current `.env.example` includes:

```text
AI_RESUME_ANALYSIS_PROVIDER=gemini
AI_RESUME_ANALYSIS_MODEL=gemini-2.5-flash
AI_RESUME_ANALYSIS_TIMEOUT=120
AI_RESUME_ANALYSIS_TEMPERATURE=0.1
GEMINI_API_KEY=
```

Rules:

- All provider/model/timeout/temperature values must come from config/env.
- Do not hardcode API keys.
- Do not expose provider exception stack traces through API responses.
- Keep prompt and schema changes covered by tests.

---

## 20. Testing Rules

Run tests after code changes:

```powershell
php artisan test
```

When working inside Docker:

```powershell
docker compose exec app php artisan test
```

Feature test requirements:

- Use `RefreshDatabase`.
- Test through HTTP endpoints for API behavior.
- Use Sanctum bearer tokens with `withToken(...)` or the existing nearby test
  pattern.
- Assert status codes and important JSON paths.
- Assert database state for mutations.
- Test authentication requirements.
- Test authorization/wrong-owner behavior.
- Test validation failures for required and domain-specific fields.

Unit test requirements:

- Unit-test normalization and technical services where API tests would be too
  broad.
- Do not mock Eloquent models.
- Do not hit real external services.

AI test requirements:

- Use `ResumeAnalysisAgent::fake(...)`.
- Use `preventStrayPrompts()`.
- Include failure tests for AI/provider/invalid-payload paths.

Factories:

- `UserFactory` exists.
- Domain-specific factories do not currently exist. Existing tests create domain
  models with local helper methods. You may add factories when they reduce real
  duplication, but do not rewrite unrelated tests just to introduce them.

---

## 21. Formatting And Style

Before finishing code changes, run Pint on changed PHP files:

```powershell
vendor/bin/pint --dirty
```

or target specific files:

```powershell
vendor/bin/pint app/Domains/Application/Actions/MoveApplicationAction.php
```

Style rules:

- New PHP files must start with `<?php`, then `declare(strict_types=1);`, then
  namespace.
- Prefer `final` classes for app code.
- Use typed parameters and return types.
- Prefer constructor property promotion and readonly dependencies.
- Use early returns to avoid deep nesting.
- Keep public methods small and focused.
- Use DocBlocks only when PHP types cannot express the shape, especially array
  shapes for Resources/tests.
- Keep comments rare and useful. Do not add comments that merely narrate obvious
  code.
- Preserve nearby style in legacy files while improving touched code safely.

Laravel conventions in this repo:

- Fillable fields are often declared with `#[Fillable([...])]`.
- User hidden fields are declared with `#[Hidden([...])]`.
- Casts are defined in `protected function casts(): array`.
- Resources use `toISOString()` for timestamps.
- Tests often use helper methods instead of factories for non-User models.

---

## 22. Docker And Local Development

Docker is present and documented in `DOCKER.md`.

Current services:

- `server`: Nginx on `http://127.0.0.1:8000`
- `app`: PHP 8.4 FPM with Supervisor
- `postgres`: PostgreSQL exposed on host port `5433`
- `redis`: Redis exposed on host port `6379`

Useful commands:

```powershell
docker compose up -d --build --remove-orphans
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose logs -f app
docker compose exec redis redis-cli ping
```

Do not edit Docker files as part of normal backend feature work unless the user
asks or the feature truly requires runtime changes.

---

## 23. Environment

Do not assume `.env.example` is production truth. It is a local starter file.

Important env values:

```text
APP_NAME
APP_ENV
APP_KEY
APP_DEBUG
APP_URL

DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

QUEUE_CONNECTION
REDIS_CLIENT
REDIS_HOST
REDIS_PASSWORD
REDIS_PORT

AI_RESUME_ANALYSIS_PROVIDER
AI_RESUME_ANALYSIS_MODEL
AI_RESUME_ANALYSIS_TIMEOUT
AI_RESUME_ANALYSIS_TEMPERATURE
GEMINI_API_KEY
```

Rules:

- Never commit real `.env` secrets.
- Keep tests independent of local Postgres, Redis, and AI credentials.
- If a new config value is required, add it to config and `.env.example`.

---

## 24. Error Handling

Expected API error shapes should follow Laravel conventions:

Validation:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Validation error detail."]
  }
}
```

Authorization/not found/server errors:

```json
{
  "message": "Human readable error message."
}
```

Status expectations:

| Situation | Status |
| --- | --- |
| Unauthenticated | 401 |
| Wrong owner direct resource access | 403 |
| Missing resource | 404 |
| Validation or domain field failure | 422 |
| Successful create | 201 |
| Successful delete | 204 |

Do not return `200` with an error message body.

---

## 25. Git And Change Safety

- Check `git status --short` before editing.
- Treat uncommitted changes as user work unless you made them.
- Do not run destructive git commands such as `git reset --hard` or
  `git checkout --` unless explicitly requested.
- Keep diffs focused on the user request.
- Do not reformat unrelated files.
- Do not rename classes or move domains for style purity.
- If an unrelated file is dirty, leave it alone.

---

## 26. Before Marking Work Done

For code changes:

1. Re-read the relevant existing domain files.
2. Confirm route, request, DTO, action, model, resource, and test coverage are
   aligned.
3. Run focused tests for the touched domain when available.
4. Run `php artisan test` before final delivery when feasible.
5. Run Pint on changed PHP files.
6. Report any tests or formatting commands that could not be run.

For documentation-only changes:

1. Verify the documentation matches the current codebase.
2. Do not run Pint unless PHP files changed.
3. Run tests only when the documentation change depends on executable behavior
   that needs confirmation.

Never claim a code task is complete when relevant tests are failing.

