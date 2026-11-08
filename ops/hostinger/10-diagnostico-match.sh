#!/usr/bin/env bash
# Mostra por que as notas do match estão como estão: calcula de novo para o usuário 1
# e imprime, para as 10 melhores vagas, quanto cada critério contribuiu.
# Só dados de vagas públicas e números; do perfil, apenas nível/escolaridade/contagens.
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
php artisan faro:compute-matches --user=1 --sync 2>&1 | tail -2
php artisan tinker --execute='
use App\Models\JobPosting; use App\Services\Matching\{MatchScorer, Vector, CareerFit};
$u = App\Models\User::find(1); $p = $u->profile;
echo "perfil: nível=".($p->desired_level?->value ?? "-")." aceitos=".implode(",", CareerFit::acceptedLevels($p) ?? ["(todos)"])." escolaridade=".($p->education_level?->value ?? "-")." área=".($p->area ?? "-")." habilidades=".$p->skills()->count()." modalidades=".implode(",", $p->desired_modalities ?? [])." contratos=".implode(",", $p->desired_contracts ?? [])." embedding=".($p->embedding ? "sim" : "não").PHP_EOL;
$ids = array_fill_keys($p->skills()->pluck("skills.id")->all(), true);
$sims = [];
foreach (JobPosting::active()->whereNotNull("embedded_at")->get(["id", "embedding"]) as $j) { $s = Vector::cosine($p->embedding, $j->embedding); if ($s !== null) $sims[] = $s; }
sort($sims); $n = count($sims);
if ($n) printf("cosseno perfil x vagas (%d): min %.2f · mediana %.2f · p90 %.2f · máx %.2f%s", $n, $sims[0], $sims[intdiv($n, 2)], $sims[(int) ($n * 0.9)], $sims[$n - 1], PHP_EOL);
$req = JobPosting::active()->withCount(["skills as req" => fn ($q) => $q->where("is_required", true)])->get()->avg("req");
echo "média de habilidades obrigatórias por vaga: ".round($req, 1).PHP_EOL;
echo "nota | hab | sim(cos) | área | nível | modelo | vaga".PHP_EOL;
foreach ($u->jobMatches()->orderByDesc("score")->limit(10)->with("jobPosting.skills")->get() as $m) {
  $j = $m->jobPosting; $r = app(MatchScorer::class)->score($p, $ids, $j, $m->similarity); $x = $r["parts"];
  $f = fn ($v) => $v === null ? "  ?" : str_pad((string) round($v * 100), 3, " ", STR_PAD_LEFT);
  echo str_pad($m->score, 4)." | ".$f($x["skills"])." | ".$f($x["similarity"])."(".($m->similarity !== null ? number_format($m->similarity, 2) : "-").") | ".$f($x["area"])." | ".$f($x["level"])." | ".$f($x["logistics"])." | ".mb_substr($j->title, 0, 45)." [".($j->level?->value ?? "-")."/".($j->area ?? "-")."] obrig=".$j->skills->where("pivot.is_required", true)->count().PHP_EOL;
}' 2>&1 | tail -16
true
