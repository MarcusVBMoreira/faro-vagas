# Roadmap

Project rule: **every step ends up live**. Each push to `main` runs the tests and, if they pass, deploys to https://faro.marcusmoreira.com.br.

## Step 0 · Foundation ✅
- [x] Name, logo, color palette and typography ([BRAND.md](BRAND.md))
- [x] Laravel 12 project set up for Brazil (`pt_BR` locale, São Paulo time zone)
- [x] Full database model: migrations, models, enums and seeders ([DATABASE.md](DATABASE.md))
- [x] Home page with the visual identity
- [x] Automated tests (PHPUnit) running on MySQL 8 in GitHub Actions
- [x] Automatic deploy to Hostinger over SSH ([DEPLOY.md](DEPLOY.md))
- [x] Configure the secrets and run the first deploy
- [x] Local AI benchmark on the VPS and endpoint published at `ia.marcusmoreira.com.br` (HTTPS + token)

## Week 1 · Accounts and AI profile ✅
- [x] Sign up, login (with attempt throttling), logout and password reset
- [x] Reset emails through Hostinger SMTP (`no-reply@marcusmoreira.com.br`)
- [x] PDF résumé upload (up to 5 MB, 5 per day) and text extraction (`smalot/pdfparser`)
- [x] Self-hosted AI on the VPS (Ollama + `qwen2.5:3b`) with a Laravel client, token, usage log and daily limit
- [x] The AI reads the résumé and returns JSON (constrained by a JSON Schema), fixed by `ProfileNormalizer`
- [x] "My profile" page: review skills, level, city and work mode; live analysis status
- [x] Privacy policy and account deletion that erases all data (LGPD)
- **Deliverable:** I upload my résumé and see my profile built.

## Week 1.5 · Every profession, safer accounts, three languages ✅
Feedback after the first real test: the product looked like a tech-only job board. Acting as an HR director, the profile was redesigned using the categories of the big Brazilian job sites (Catho, InfoJobs, Gupy, Vagas.com) as a reference.
- [x] **28 professional fields** (administration, sales, healthcare, logistics, manufacturing, education, food service, construction, IT...)
- [x] **14 career levels**, from apprentice and intern to entry-level, technician, mid, senior, supervisor, manager and director
- [x] Contract types (CLT, PJ, internship, apprentice, temporary, freelance), salary expectation, start availability, "open to work"
- [x] Education level (elementary school to doctorate), work history and education entries, languages with level, driver's license (CNH), willingness to travel or relocate, WhatsApp and LinkedIn
- [x] Optional, consent-based disability (PcD) field, used only to show affirmative openings and **never** in the match score
- [x] Profile completeness meter
- [x] The AI prompt and schema cover every field and extract experiences and education; `ProfileNormalizer` maps free text to the fixed lists
- [x] Skill catalog seeded for every field (Excel, customer service, forklift, NR-35, SAP, TOTVS, AutoCAD, Libras...)
- [x] **Email verification with a 6-digit code** at sign-up (HMAC-hashed, 15 min, 5 attempts, single use)
- [x] **Optional two-step login** by email code
- [x] **Optional profile photo** (center-cropped to 400×400 JPEG, which also strips EXIF/GPS data)
- [x] **Portuguese, English and Spanish**: language switcher, browser detection, saved per account; emails go out in the user's language
- [x] Security settings: change password, toggle 2FA

## Week 2 · Job collection ✅
Scope decision: **Greater São Paulo (39 cities) + remote jobs**, every field. `FARO_JOBS_REGION=brasil` opens it to the whole country.
- [x] `JobSourceConnector` interface with connectors, all through official or public APIs:
  - **Adzuna Brasil** (aggregator of Brazilian job sites, every field, free key)
  - **Jooble Brasil** (aggregator, free key on request)
  - **Remotive** (international remote jobs, kept only when open to Brazil: Worldwide, LATAM, Americas)
  - **GitHub issues** (tech community boards)
  - The unofficial Gupy portal search was evaluated and dropped: it no longer answers server requests.
- [x] `faro:fetch-jobs` scheduled daily at 05:40: region filter, de-duplication (same source id, same URL, or same title + company + city across aggregators), stale jobs closed after 5 days unseen or 45 days old
- [x] Fast deterministic classification (contract, work model, level, field, disability-inclusive) and an AI pass in a low-priority queue for field, level, minimum education and required/nice-to-have skills, grounded in the posting text
- [x] Jobs page with search and filters (field, level, work model, contract, disability-inclusive, international) and a detail page with source credit
- [x] Add a job by hand (private to the user who added it)
- [x] "I accept remote jobs from other countries" profile preference
- [x] Adzuna and Jooble keys in production (the Jooble key is per country: br.jooble.org)
- **Deliverable:** the database fills itself with job postings.

## Week 3 · AI matching ✅
- [x] Profile and posting embeddings (`bge-m3` on the VPS); postings embedded in batches every 15 minutes, the profile only when its text changes
- [x] `MatchService`: SQL filters (active, visible, international only if opted in), cosine similarity in PHP, best 50 stored
- [x] `MatchScorer`, a score computed in code: required/nice-to-have skills 40, AI similarity 30, field 15, level 10, work model and contract 5; unknown items have their weight redistributed; education below the requirement costs 10; disability data is never read
- [x] Strengths and gaps per job, translated at display time
- [x] "Jobs for you" page and the score on the job page
- [x] "Good match / not for me" feedback (hidden jobs stay hidden after recalculation)
- [x] Recalculated when the profile changes and every hour
- **Deliverable:** the heart of the project working.

## Week 4 · Product and polish ✅
- [x] Application Kanban board (SortableJS drag and drop, plus a selector that works without JavaScript), notes, stage history
- [x] Daily email digest at 07:50 with up to 5 new jobs (score ≥ 55), signed unsubscribe link
- [x] "What to learn": most frequent gaps across the person's best matches
- [x] Career-stage filter (accepted levels, degree requirements) after real-user feedback
- [x] README with the match funnel diagram
- [x] Demo GIF in the README and home page updated with live numbers
- [ ] Faster résumé analysis on the VPS (smaller prompt, streaming, model tuning)
- **Deliverable:** portfolio-ready project.

## After the first month
- Study plan based on the most frequent gaps
- Cover letter generated for each job
- Dark theme
- Move to the VPS (always-on queue, native vector search) and document the migration
