<?php

/*
 * Mensagens de validação em português. Regras que não estão aqui usam o inglês do framework.
 */
return [
    'accepted' => 'Você precisa aceitar :attribute.',
    'array' => 'O campo :attribute é inválido.',
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute é inválido.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'email' => 'Informe um e-mail válido.',
    'enum' => 'O valor escolhido para :attribute é inválido.',
    'file' => 'O campo :attribute precisa ser um arquivo.',
    'in' => 'O valor escolhido para :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'file' => 'O arquivo :attribute deve ter no máximo :max KB.',
        'numeric' => 'O campo :attribute deve ser no máximo :max.',
        'string' => 'O campo :attribute deve ter no máximo :max caracteres.',
    ],
    'mimes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'mimetypes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'password' => [
        'letters' => 'A :attribute deve ter pelo menos uma letra.',
        'mixed' => 'A :attribute deve ter letras maiúsculas e minúsculas.',
        'numbers' => 'A :attribute deve ter pelo menos um número.',
        'symbols' => 'A :attribute deve ter pelo menos um símbolo.',
        'uncompromised' => 'Essa :attribute apareceu em um vazamento de dados. Escolha outra.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'size' => [
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',
    'uploaded' => 'Não foi possível enviar :attribute. Tente de novo.',

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'terms' => 'os termos de uso',
        'resume' => 'currículo',
        'headline' => 'título',
        'desired_level' => 'nível desejado',
        'area' => 'área',
        'city' => 'cidade',
        'state' => 'estado',
        'desired_modalities' => 'modalidades',
        'current_semester' => 'semestre atual',
        'graduation_year' => 'ano de conclusão',
        'english_level' => 'nível de inglês',
        'skills' => 'habilidades',
        'confirm_email' => 'confirmação de e-mail',
    ],
];
