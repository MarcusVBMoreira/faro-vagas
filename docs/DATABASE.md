# Modelagem do banco de dados

Banco: **MySQL** (Hostinger) em produção, MySQL 8 nos testes do GitHub Actions e SQLite opcional no desenvolvimento local.
Todas as tabelas são criadas pelas migrations em `database/migrations/`.

## Diagrama

```mermaid
erDiagram
    users ||--o| profiles : "tem um"
    users ||--o{ resumes : "envia"
    users ||--o{ job_matches : "recebe"
    users ||--o{ applications : "acompanha"
    users ||--o{ ai_usage_logs : "consome"

    profiles }o--o{ skills : "profile_skill"
    job_postings }o--o{ skills : "job_posting_skill"

    job_sources ||--o{ job_postings : "fornece"
    job_postings ||--o{ job_matches : "avaliada em"
    job_postings ||--o{ applications : "candidatura"
    applications ||--o{ application_events : "histórico"

    users {
        bigint id PK
        string name
        string email UK
        string password
    }
    profiles {
        bigint id PK
        bigint user_id FK,UK
        string desired_level "estagio | junior | pleno | senior"
        string area
        string city
        char state
        json desired_modalities "remoto, hibrido, presencial"
        tinyint current_semester
        json embedding "vetor da IA"
        bool digest_enabled
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
        decimal similarity "0 a 1"
        tinyint score "0 a 100"
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

## Tabelas

| Tabela | Para que serve |
|---|---|
| `users` | Contas (padrão do Laravel). |
| `profiles` | Perfil profissional montado pela IA a partir do currículo e revisado pelo usuário. Guarda o **embedding** (vetor) usado no match. |
| `skills` | Catálogo único de habilidades (PHP, React, Git...), compartilhado entre perfis e vagas. |
| `profile_skill` | Habilidades de cada perfil, com nível de 1 a 5 e a origem (`ai` ou `manual`). |
| `resumes` | Currículos enviados e o texto extraído do PDF. Só um fica `is_current`. |
| `job_sources` | Fontes de vagas. Cada `type` terá um conector próprio. |
| `job_postings` | Vagas coletadas. O nome não é `jobs` porque essa tabela já é a fila do Laravel. |
| `job_posting_skill` | Tecnologias de cada vaga, separando obrigatórias de diferenciais. |
| `job_matches` | Resultado do funil de match: similaridade (embeddings) + nota, pontos fortes e lacunas (IA). |
| `applications` | Candidaturas no Kanban, com a posição dentro de cada coluna. |
| `application_events` | Histórico de mudanças de status, criado automaticamente pelo model `Application`. |
| `ai_usage_logs` | Cada chamada à IA, para controlar custo e limitar uso por usuário. |
| `jobs`, `failed_jobs`, `cache`, `sessions`... | Infraestrutura do Laravel (fila, cache e sessões no próprio banco, sem precisar de Redis). |

## Decisões

- **Enums como texto + enums do PHP** (`app/Enums`): o banco guarda `'estagio'`, o código usa `JobLevel::Estagio`. Funciona igual no MySQL e no SQLite e é fácil adicionar um valor novo sem migration.
- **Embeddings em JSON:** a hospedagem compartilhada não tem banco vetorial. Os filtros SQL (nível, modalidade, cidade) reduzem as vagas para algumas centenas e a similaridade é calculada em PHP. Se um dia migrar para a VPS com pgvector ou MariaDB 11.7+, só a classe de match muda.
- **`content_hash` único:** impede salvar a mesma vaga duas vezes, mesmo vindo de fontes diferentes.
- **Apagar a conta apaga tudo do usuário** (`cascadeOnDelete`): perfil, currículos, matches e candidaturas. Exigência da LGPD. As vagas continuam, porque não pertencem a ninguém.
