<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V2: o Faro passa a atender todas as áreas (não só tecnologia) e ganha
 * verificação de e-mail por código, 2FA por e-mail, foto de perfil e idioma do site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('pt_BR')->after('email');
            $table->boolean('two_factor_email')->default(false)->after('password');
            $table->string('avatar_path')->nullable()->after('two_factor_email');
        });

        // Códigos de 6 dígitos enviados por e-mail (cadastro e login em duas etapas).
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 20);              // verify_email | login
            $table->char('code_hash', 64);              // HMAC do código; o código em si nunca é salvo
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('desired_role', 120)->nullable()->after('headline');        // cargo desejado, texto livre
            $table->json('desired_contracts')->nullable()->after('desired_modalities');  // clt, pj, estagio...
            $table->string('education_level', 30)->nullable()->after('desired_contracts');
            $table->json('languages')->nullable()->after('english_level');              // [{language, level}]
            $table->string('phone', 20)->nullable()->after('state');
            $table->string('linkedin_url')->nullable()->after('phone');
            $table->string('portfolio_url')->nullable()->after('linkedin_url');
            $table->unsignedInteger('salary_expectation')->nullable();                 // R$ por mês
            $table->string('availability', 20)->nullable();                             // imediata, 15_dias...
            $table->boolean('willing_to_relocate')->default(false);
            $table->boolean('willing_to_travel')->default(false);
            $table->json('driver_license')->nullable();                                 // ["A","B"]
            $table->boolean('open_to_work')->default(true);
            // Dado sensível (LGPD): só com consentimento, e nunca entra na nota do match.
            $table->boolean('is_pcd')->nullable();
            $table->timestamp('pcd_consent_at')->nullable();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('role', 120);
            $table->string('company', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->date('start_date')->nullable();       // sempre dia 1 do mês
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->string('source', 10)->default('manual'); // ai | manual
            $table->timestamps();
        });

        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('course', 150);
            $table->string('institution', 150)->nullable();
            $table->string('level', 30)->nullable();       // App\Enums\EducationLevel
            $table->string('status', 20)->nullable();      // cursando | concluido | trancado
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->string('source', 10)->default('manual');
            $table->timestamps();
        });

        // A área antes era texto livre ("Desenvolvimento Web"): vira o cargo desejado,
        // e a área passa a ser uma das categorias de App\Support\Areas.
        DB::table('profiles')->whereNotNull('area')->update(['desired_role' => DB::raw('area')]);
        DB::table('profiles')->update(['area' => null]);

        // O nível de inglês vira um item da lista de idiomas.
        foreach (DB::table('profiles')->whereNotNull('english_level')->get(['id', 'english_level']) as $row) {
            DB::table('profiles')->where('id', $row->id)->update([
                'languages' => json_encode([['language' => 'ingles', 'level' => $row->english_level]]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('educations');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('verification_codes');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'desired_role', 'desired_contracts', 'education_level', 'languages', 'phone',
                'linkedin_url', 'portfolio_url', 'salary_expectation', 'availability',
                'willing_to_relocate', 'willing_to_travel', 'driver_license', 'open_to_work',
                'is_pcd', 'pcd_consent_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'two_factor_email', 'avatar_path']);
        });
    }
};
