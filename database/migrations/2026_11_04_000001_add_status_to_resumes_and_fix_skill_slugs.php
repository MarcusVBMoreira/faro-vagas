<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Andamento da análise do currículo pela IA (feita em fila).
        Schema::table('resumes', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_current'); // pending | processing | done | failed
            $table->string('error_message')->nullable()->after('status');
        });

        // Correção: o slug de "C#" tinha virado "c" (o Str::slug apaga o "#").
        DB::table('skills')->where('name', 'C#')->update(['slug' => 'c-sharp']);
        DB::table('skills')->where('name', 'C++')->update(['slug' => 'c-plus-plus']);
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
        });

        DB::table('skills')->where('name', 'C#')->update(['slug' => 'c']);
    }
};
