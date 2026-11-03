<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perfil profissional do usuário, montado pela IA a partir do currículo e revisado por ele.
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();              // "Estudante de Eng. de Software focado em back-end"
            $table->string('desired_level', 20)->nullable();     // App\Enums\JobLevel
            $table->string('area', 60)->nullable();              // back-end, front-end, dados...
            $table->string('city', 80)->nullable();
            $table->char('state', 2)->nullable();
            $table->json('desired_modalities')->nullable();      // ["remoto", "hibrido"]
            $table->unsignedTinyInteger('current_semester')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('english_level', 20)->nullable();
            $table->text('summary')->nullable();
            $table->boolean('digest_enabled')->default(true);   // resumo diário por e-mail
            $table->json('embedding')->nullable();               // vetor gerado pela IA
            $table->string('embedding_model', 80)->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->timestamps();
        });

        // Catálogo único de habilidades, compartilhado entre perfis e vagas.
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 80)->unique();
            $table->string('category', 40)->nullable();          // linguagem, framework, banco, ferramenta, soft skill
            $table->timestamps();
        });

        Schema::create('profile_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('proficiency')->default(1); // 1 a 5
            $table->string('source', 10)->default('ai');           // ai | manual
            $table->timestamps();

            $table->unique(['profile_id', 'skill_id']);
        });

        // Currículos enviados. Só um fica marcado como atual.
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->longText('extracted_text')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('profile_skill');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('profiles');
    }
};
