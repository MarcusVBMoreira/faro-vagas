#!/usr/bin/env bash
# Lista as habilidades obrigatórias que a IA extraiu de algumas vagas de estágio/júnior em tecnologia
# (dados públicos das vagas), para calibrar a comparação com o perfil.
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
php artisan tinker --execute='
foreach (App\Models\JobPosting::active()->where("area", "tecnologia")->whereIn("level", ["estagio", "junior", "trainee"])->has("skills")->with("skills")->inRandomOrder()->limit(12)->get() as $j)
  echo mb_substr($j->title, 0, 40)." → obrig: ".$j->skills->where("pivot.is_required", true)->pluck("name")->implode(", ")." | dif: ".$j->skills->where("pivot.is_required", false)->pluck("name")->implode(", ").PHP_EOL;
' 2>&1 | tail -12
true
