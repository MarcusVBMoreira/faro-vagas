<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Semana 3: controle dos embeddings e do último cálculo de match.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->char('embedding_hash', 40)->nullable()->after('embedding'); // texto que gerou o vetor
            $table->timestamp('matches_computed_at')->nullable();
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->timestamp('embedded_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn(['embedding_hash', 'matches_computed_at']));
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropIndex(['embedded_at']);
            $table->dropColumn('embedded_at');
        });
    }
};
