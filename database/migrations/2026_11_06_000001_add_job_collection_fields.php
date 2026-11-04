<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Semana 2: campos para vagas de todas as áreas e vagas adicionadas pelo usuário.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('job_source_id')
                ->constrained()->cascadeOnDelete();                   // vaga adicionada à mão (privada)
            $table->string('area', 40)->nullable()->after('level')->index();   // App\Support\Areas
            $table->string('education_level', 30)->nullable()->after('area'); // escolaridade mínima
            $table->boolean('is_pcd')->default(false)->after('contract_type'); // vaga afirmativa PcD
            $table->boolean('is_international')->default(false)->after('is_pcd'); // remota de outro país
            $table->string('location_text', 160)->nullable()->after('state');  // como a fonte escreveu
            $table->string('source_category', 120)->nullable();                // categoria na fonte
            $table->timestamp('last_seen_at')->nullable()->index();            // última coleta que viu a vaga
            $table->index(['status', 'published_at']);
        });

        // Preferência do perfil: mostrar vagas remotas de empresas de fora (exigem outro idioma).
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('accepts_international')->default(false)->after('open_to_work');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn('accepts_international'));

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['area', 'education_level', 'is_pcd', 'is_international', 'location_text', 'source_category', 'last_seen_at']);
        });
    }
};
