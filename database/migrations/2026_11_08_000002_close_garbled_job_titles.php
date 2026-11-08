<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Algumas vagas chegaram de um agregador com o título trocado por "??????".
 * A coleta agora descarta esses títulos; aqui fechamos as que já estavam no banco.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('job_postings')->where('title', 'like', '%???%')->update(['status' => 'closed']);
    }

    public function down(): void
    {
        // Sem volta: as vagas fechadas reabrem sozinhas se a fonte mandar um título válido.
    }
};
