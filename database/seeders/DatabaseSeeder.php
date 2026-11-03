<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados básicos (rodam também em produção) + usuário de teste só no ambiente local.
     */
    public function run(): void
    {
        $this->call([
            SkillSeeder::class,
            JobSourceSeeder::class,
        ]);

        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'teste@faro.local'],
                ['name' => 'Usuário de Teste', 'password' => 'password'],
            );
        }
    }
}
