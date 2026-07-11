<div align="center">

# 🧠 ApplyAI — API

**The Laravel backend for an AI‑powered résumé optimization & job‑application tracking platform.**

Upload résumés, analyze them against job descriptions with AI, generate tailored résumé PDFs, and manage applications on a kanban board.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-Queues-DC382D?logo=redis&logoColor=white)
![Google Gemini](https://img.shields.io/badge/Google_Gemini-AI-4285F4?logo=google&logoColor=white)

**Frontend / web app →** [marwaneqada/applyai-web](https://github.com/marwaneqada/applyai-web)

</div>

---

## ✨ Overview

ApplyAI API exposes JSON APIs and PDF responses for a separate frontend application. It owns the platform's core business logic: authentication, résumé storage & parsing, queued AI analysis, structured résumé generation, PDF rendering, and application tracking — all organized with a **domain‑driven** structure.

## 🚀 Features

- 🔐 Token‑based authentication with **Laravel Sanctum**
- 📄 Résumé PDF upload and text extraction
- 🧠 **Queued** AI résumé‑to‑job analysis (Google Gemini, structured output)
- 📊 Structured analysis results — match score, keywords, gaps, strengths, weaknesses, rewritten bullet suggestions, and a cover letter
- 🧩 Async résumé structuring for PDF generation (cached)
- 🎯 Résumé PDF generation with multiple templates — `harvard`, `modern`, `minimal`
- 🗂️ Job‑application tracking with kanban statuses
- ↕️ Drag‑and‑drop application ordering using stable float positions
- 🛡️ User‑ownership checks on every protected resource

## 🧱 Architecture

Domain‑driven modules under `app/Domains`:

| Domain | Responsibility |
| --- | --- |
| **Auth** | Registration, login, token issuing |
| **Resume** | PDF upload, storage, text extraction, parse status |
| **Analysis** | Queued AI analysis, structured results, résumé structuring & PDF generation |
| **Application** | Kanban application tracking, ordering, stats |

Each domain uses thin controllers → **Actions** → **DTOs**, with responses shaped by API Resources. Heavy work (AI analysis, résumé structuring) runs as **queued jobs** on Redis.

## 🔌 API

All routes are prefixed with `/api`. Protected routes require `Authorization: Bearer <token>`.

| Method | Endpoint | Auth | Description |
| --- | --- | :---: | --- |
| `POST` | `/auth/register` | – | Create an account, returns `{ user, token }` |
| `POST` | `/auth/login` | – | Log in, returns `{ user, token }` |
| `POST` | `/auth/logout` | ✅ | Revoke the current token |
| `GET` | `/me` | ✅ | Current user |
| `GET` / `POST` | `/resumes` | ✅ | List / upload (PDF) résumés |
| `GET` / `DELETE` | `/resumes/{id}` | ✅ | Show / delete a résumé |
| `GET` / `POST` | `/analyses` | ✅ | List / create an analysis (queued) |
| `GET` | `/analyses/{id}` | ✅ | Show an analysis with its result |
| `GET` | `/analyses/{id}/status` | ✅ | Poll analysis status |
| `POST` | `/analyses/{id}/resume/structure` | ✅ | Start résumé structuring (queued) |
| `GET` | `/analyses/{id}/resume/structure/status` | ✅ | Poll structuring status |
| `POST` | `/analyses/{id}/resume/pdf` | ✅ | Generate a résumé PDF (`template`) |
| `GET` | `/applications` | ✅ | List applications grouped by status |
| `GET` | `/applications/stats` | ✅ | Application stats |
| `POST` | `/applications` | ✅ | Create an application |
| `GET` / `PATCH` / `DELETE` | `/applications/{id}` | ✅ | Show / update / delete |
| `PATCH` | `/applications/{id}/move` | ✅ | Move a card between/within columns |

## 🧰 Tech stack

| Area | Choice |
| --- | --- |
| Framework | Laravel 12 (PHP 8.4) |
| Auth | Laravel Sanctum |
| Database | PostgreSQL (Docker) / SQLite (local default) |
| Queue | Redis + Laravel Horizon |
| AI | Google Gemini via Laravel AI (schema‑constrained structured output) |
| PDF | `barryvdh/laravel-dompdf` (render) · `smalot/pdfparser` (extract) |

## 🔄 Product flow

1. A user registers or logs in.
2. The user uploads a résumé PDF → the backend extracts its text.
3. The user submits a job description → the backend **queues an AI analysis job**.
4. The frontend polls until the analysis is `completed`.
5. The user reviews the match score, keyword gaps, strengths, weaknesses, rewritten bullets, and cover letter.
6. The user starts résumé structuring → the backend prepares and caches structured résumé data.
7. The frontend polls until the structured résumé is ready.
8. The user generates a PDF using one of the supported templates.
9. The user creates and manages application cards on the kanban board.

## 🏁 Getting started

### Local

```bash
composer install
cp .env.example .env
php artisan key:generate

# set GEMINI_API_KEY and your DB connection in .env, then:
php artisan migrate

# run the queue worker (analysis & structuring are queued) + the server
php artisan queue:work        # or: php artisan horizon
php artisan serve
```

### Docker

```bash
docker compose up -d          # nginx + php-fpm + postgres + redis
```

### Key environment variables

| Variable | Description |
| --- | --- |
| `AI_RESUME_ANALYSIS_PROVIDER` | AI provider (default `gemini`) |
| `AI_RESUME_ANALYSIS_MODEL` | Model (default `gemini-2.5-flash`) |
| `GEMINI_API_KEY` | Your Google Gemini API key |
| `QUEUE_CONNECTION` | `redis` (analysis & structuring run as jobs) |

> ⚠️ The analysis and résumé‑structuring endpoints only complete when a **queue worker is running**.

## 🔗 Related

- **Frontend / web app:** [marwaneqada/applyai-web](https://github.com/marwaneqada/applyai-web)

<div align="center"><sub>Built with Laravel · PHP · PostgreSQL · Redis · Google Gemini</sub></div>
