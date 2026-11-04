# Database model

Database: **MySQL** (Hostinger) in production, MySQL 8 in the GitHub Actions tests and optional SQLite for local development.
Every table is created by the migrations in `database/migrations/`.

## Diagram

```mermaid
erDiagram
    users ||--o| profiles : "has one"
    users ||--o{ resumes : "uploads"
    users ||--o{ verification_codes : "receives"
    users ||--o{ job_matches : "receives"
    users ||--o{ applications : "tracks"
    users ||--o{ ai_usage_logs : "consumes"

    profiles ||--o{ experiences : "work history"
    profiles ||--o{ educations : "education"
    profiles }o--o{ skills : "profile_skill"
    job_postings }o--o{ skills : "job_posting_skill"

    job_sources ||--o{ job_postings : "provides"
    job_postings ||--o{ job_matches : "scored in"
    job_postings ||--o{ applications : "applied to"
    applications ||--o{ application_events : "history"

    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string locale "pt_BR | en | es"
        bool two_factor_email
        string avatar_path "public disk"
    }
    verification_codes {
        bigint id PK
        bigint user_id FK
        string purpose "verify_email | login"
        char code_hash "HMAC-SHA256, never the code"
        tinyint attempts
        timestamp expires_at
        timestamp consumed_at
    }
    profiles {
        bigint id PK
        bigint user_id FK,UK
        string desired_role
        string area "App\\Support\\Areas key"
        string desired_level "App\\Enums\\JobLevel"
        json desired_contracts "clt, pj, estagio..."
        json desired_modalities "remoto, hibrido, presencial"
        int salary_expectation
        string availability
        bool open_to_work
        string education_level "App\\Enums\\EducationLevel"
        json languages "[{language, level}]"
        json driver_license "A..E"
        string city
        char state
        string phone
        string linkedin_url
        bool is_pcd "optional, consent only"
        timestamp pcd_consent_at
        json embedding "AI vector"
        bool digest_enabled
    }
    experiences {
        bigint id PK
        bigint profile_id FK
        string role
        string company
        date start_date
        date end_date
        bool is_current
        string source "ai | manual"
    }
    educations {
        bigint id PK
        bigint profile_id FK
        string course
        string institution
        string level
        string status "cursando | concluido | trancado"
        smallint start_year
        smallint end_year
        string source "ai | manual"
    }
    skills {
        bigint id PK
        string name UK
        string slug UK
        string category
    }
    resumes {
        bigint id PK
        bigint user_id FK
        string storage_path
        longtext extracted_text
        string status "pending | processing | done | failed"
        bool is_current
    }
    job_sources {
        bigint id PK
        string slug UK
        string type "github_issues | api | manual"
        json config
        timestamp last_fetched_at
    }
    job_postings {
        bigint id PK
        bigint job_source_id FK
        string external_id
        string url
        string title
        string company
        longtext description
        string level
        string modality
        string status "active | closed"
        json embedding
        char content_hash UK
    }
    job_matches {
        bigint id PK
        bigint user_id FK
        bigint job_posting_id FK
        decimal similarity "0 to 1"
        tinyint score "0 to 100"
        json strengths
        json gaps
        text explanation
        string feedback "good | bad"
    }
    applications {
        bigint id PK
        bigint user_id FK
        bigint job_posting_id FK
        string status "saved ... rejected"
        int position
        text notes
    }
    application_events {
        bigint id PK
        bigint application_id FK
        string from_status
        string to_status
    }
    ai_usage_logs {
        bigint id PK
        bigint user_id FK
        string operation
        string model
        int input_tokens
        int output_tokens
    }
```

## Tables

| Table | Purpose |
|---|---|
| `users` | Accounts, plus the preferred language, the optional 2FA flag and the optional photo. |
| `verification_codes` | 6-digit email codes for sign-up verification and two-step login. Only an HMAC of the code is stored; codes expire after 15 minutes, allow 5 attempts and are single-use. |
| `profiles` | Professional profile built by the AI from the résumé and reviewed by the user. Covers every field: desired role, field, level, contracts, salary, availability, education, languages, driver's license and contact. Stores the **embedding** used for matching. |
| `experiences` | Work history (jobs, internships, volunteering, freelance). |
| `educations` | Courses: high school, technical, college, postgraduate. |
| `skills` | Single skill catalog (Excel, customer service, forklift, NR-35, PHP...), shared by profiles and postings. |
| `profile_skill` | Skills of each profile, with a 1–5 level and the source (`ai` or `manual`). |
| `resumes` | Uploaded résumés and the text extracted from the PDF. Only one is `is_current`. |
| `job_sources` | Job sources. Each `type` gets its own connector. |
| `job_postings` | Collected postings. It isn't called `jobs` because that table is Laravel's queue. |
| `job_posting_skill` | Skills of each posting, split into required and nice-to-have. |
| `job_matches` | Result of the match funnel: similarity (embeddings) plus score, strengths and gaps. |
| `applications` | Applications on the Kanban board, with their position inside each column. |
| `application_events` | Status change history, created automatically by the `Application` model. |
| `ai_usage_logs` | Every AI call, to track cost and limit usage per user. |
| `jobs`, `failed_jobs`, `cache`, `sessions`... | Laravel infrastructure (queue, cache and sessions in the database itself, no Redis needed). |

## Decisions

- **Enums stored as text + PHP enums** (`app/Enums`): the database stores `'estagio'`, the code uses `JobLevel::Estagio`. Works the same on MySQL and SQLite, and adding a value needs no migration. The 28 fields live in `App\Support\Areas` for the same reason.
- **Stored values stay in Portuguese, labels are translated.** Keys such as `saude` or `ensino_medio` never change; `__()` turns them into Portuguese, English or Spanish labels at display time.
- **AI vs. manual data:** `experiences`, `educations` and `profile_skill` carry a `source`. A new résumé replaces only what the AI created; what the user typed is never overwritten.
- **Sensitive data (LGPD):** the disability field is `null` unless the user explicitly answers, `pcd_consent_at` records when, and the match score never reads it.
- **Embeddings as JSON:** shared hosting has no vector database. SQL filters (field, level, work mode, city) narrow postings down to a few hundred and similarity is computed in PHP. If the project moves to the VPS with pgvector or MariaDB 11.7+, only the match class changes.
- **Unique `content_hash`:** prevents storing the same posting twice, even from different sources.
- **Deleting the account deletes everything the user owns** (`cascadeOnDelete` plus the files): profile, photo, résumés, codes, matches and applications. Postings stay, since they belong to nobody.
