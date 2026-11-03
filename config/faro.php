<?php

/*
 * Configurações próprias do Faro.
 */
return [

    // Link do repositório, exibido no site.
    'repo_url' => env('FARO_REPO_URL', 'https://github.com/MarcusVBMoreira/faro-vagas'),

    'ai' => [
        // Provedor e modelos usados nas chamadas de IA (a integração entra na semana 1).
        'provider' => env('AI_PROVIDER', 'gemini'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL'),
        'embedding_model' => env('AI_EMBEDDING_MODEL'),

        // Limite de chamadas por usuário por dia, para controlar custo.
        'daily_limit_per_user' => (int) env('AI_DAILY_LIMIT_PER_USER', 50),
    ],

    'matching' => [
        // Quantas vagas passam da busca por similaridade para a avaliação da IA.
        'candidates_for_ai' => (int) env('MATCH_CANDIDATES_FOR_AI', 20),
    ],
];
