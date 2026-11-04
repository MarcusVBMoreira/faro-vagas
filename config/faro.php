<?php

/*
 * Configurações próprias do Faro.
 */
return [

    // Link do repositório, exibido no site.
    'repo_url' => env('FARO_REPO_URL', 'https://github.com/MarcusVBMoreira/faro-vagas'),

    'ai' => [
        // Ollama no VPS do projeto (ops/vps/04-ia-https-token.sh), acessado por HTTPS com token.
        'base_url' => rtrim((string) env('AI_BASE_URL', 'https://ia.marcusmoreira.com.br'), '/'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'qwen2.5:3b'),
        'embedding_model' => env('AI_EMBEDDING_MODEL', 'bge-m3'),

        // Em CPU (1 núcleo) o modelo 3b leva de 1 a 6 minutos para um currículo completo.
        'timeout' => (int) env('AI_TIMEOUT', 540),
        'context_window' => (int) env('AI_CONTEXT_WINDOW', 6144),

        // Limite de chamadas por usuário por dia, para proteger o servidor.
        'daily_limit_per_user' => (int) env('AI_DAILY_LIMIT_PER_USER', 50),
    ],

    'resumes' => [
        'max_kb' => 5120,
        'max_uploads_per_day' => (int) env('RESUME_MAX_UPLOADS_PER_DAY', 5),
        // Texto enviado à IA (o resto do PDF é ignorado): ~2.500 tokens.
        'max_chars_for_ai' => 9000,
    ],

    'jobs' => [
        // "grande_sp" (Grande São Paulo + remotas) ou "brasil"
        'region' => env('FARO_JOBS_REGION', 'grande_sp'),
        // Limite de vagas novas por fonte em cada coleta (a IA estrutura umas 100 por hora)
        'max_new_per_source' => (int) env('FARO_JOBS_MAX_NEW_PER_SOURCE', 200),
        // Vaga que some da fonte por X dias, ou publicada há mais de Y dias, é fechada
        'close_after_days' => (int) env('FARO_JOBS_CLOSE_AFTER_DAYS', 5),
        'max_age_days' => (int) env('FARO_JOBS_MAX_AGE_DAYS', 45),

        'adzuna' => [
            'app_id' => env('ADZUNA_APP_ID'),
            'app_key' => env('ADZUNA_APP_KEY'),
        ],
        'jooble' => [
            'api_key' => env('JOOBLE_API_KEY'),
        ],
        // Opcional: token do GitHub aumenta o limite da API (60 → 5000 por hora)
        'github_token' => env('GITHUB_JOBS_TOKEN'),
    ],

    'matching' => [
        // Quantas vagas passam da busca por similaridade para a nota final.
        'candidates' => (int) env('MATCH_CANDIDATES', 20),
    ],
];
