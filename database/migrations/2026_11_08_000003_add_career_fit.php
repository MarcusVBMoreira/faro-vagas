<?php

use App\Support\ProfileNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Níveis de vaga que a pessoa aceita (ex.: estágio + júnior) e contagem de vagas
 * que ficaram fora do match por nível ou escolaridade. Também reclassifica o nível
 * de vagas antigas pelo título ("Dev Jr", "Analista Sr", "Pleno").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->json('accepted_levels')->nullable()->after('desired_level');
            $table->unsignedInteger('matches_hidden')->nullable()->after('matches_computed_at');
        });

        DB::table('job_postings')->whereNull('level')->orderBy('id')->select(['id', 'title'])
            ->chunkById(500, function ($jobs) {
                foreach ($jobs as $job) {
                    if ($level = ProfileNormalizer::level($job->title)) {
                        DB::table('job_postings')->where('id', $job->id)->update(['level' => $level->value]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn(['accepted_levels', 'matches_hidden']));
    }
};
