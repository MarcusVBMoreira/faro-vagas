<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resultado do funil de match: similaridade (embeddings) + nota da IA.
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->decimal('similarity', 5, 4)->nullable();     // 0.0000 a 1.0000 (etapa 2)
            $table->unsignedTinyInteger('score')->nullable();    // 0 a 100 (etapa 3, IA)
            $table->json('strengths')->nullable();               // ["React", "Git"]
            $table->json('gaps')->nullable();                    // ["Docker"]
            $table->text('explanation')->nullable();
            $table->string('ai_model', 80)->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->string('feedback', 10)->nullable();          // good | bad
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'job_posting_id']);
            $table->index(['user_id', 'score']);
        });

        // Kanban de candidaturas.
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('saved');      // App\Enums\ApplicationStatus
            $table->unsignedInteger('position')->default(0);     // ordem dentro da coluna
            $table->text('notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'job_posting_id']);
            $table->index(['user_id', 'status', 'position']);
        });

        // Histórico de mudanças de status (alimenta o dashboard).
        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->timestamp('created_at')->useCurrent();
        });

        // Registro de chamadas à IA: controle de custo e limite por usuário.
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation', 40);                     // parse_resume, extract_job, embed, evaluate_match
            $table->string('model', 80);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('application_events');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('job_matches');
    }
};
