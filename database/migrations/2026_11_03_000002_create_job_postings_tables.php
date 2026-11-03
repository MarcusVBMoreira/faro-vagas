<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fontes de vagas (um conector por tipo).
        Schema::create('job_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->string('type', 30);                      // App\Enums\JobSourceType
            $table->json('config')->nullable();              // ex.: {"repo": "backend-br/vagas"}
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        // "job_postings" e não "jobs": a tabela "jobs" já é a fila do Laravel.
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();       // id da vaga na fonte (ex.: número da issue)
            $table->string('url', 500);
            $table->string('title');
            $table->string('company')->nullable();
            $table->longText('description');
            $table->string('level', 20)->nullable()->index();      // App\Enums\JobLevel
            $table->string('modality', 20)->nullable()->index();   // App\Enums\WorkModality
            $table->string('city', 80)->nullable();
            $table->char('state', 2)->nullable();
            $table->string('contract_type', 20)->nullable();       // estagio, clt, pj
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20)->default('active')->index(); // active | closed
            $table->text('ai_summary')->nullable();
            $table->timestamp('extracted_at')->nullable();             // quando a IA estruturou a vaga
            $table->json('embedding')->nullable();
            $table->string('embedding_model', 80)->nullable();
            $table->char('content_hash', 64)->unique();                // evita vaga duplicada
            $table->timestamps();

            $table->unique(['job_source_id', 'external_id']);
        });

        Schema::create('job_posting_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);   // obrigatória ou diferencial
            $table->timestamps();

            $table->unique(['job_posting_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posting_skill');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('job_sources');
    }
};
