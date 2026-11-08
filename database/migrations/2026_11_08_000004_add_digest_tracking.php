<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Semana 4: quando foi o último e-mail diário (para mandar só vagas novas). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->timestamp('last_digest_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn('last_digest_at'));
    }
};
