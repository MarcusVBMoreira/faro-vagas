<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_and_remove_an_experience(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/experiencias', [
            'role' => 'Atendente de loja',
            'company' => 'Loja Exemplo',
            'start_month' => '2022-05',
            'is_current' => '1',
            'end_month' => '2023-01', // ignorado: trabalho atual
            'description' => 'Atendimento e caixa.',
        ])->assertRedirect(route('profile.show').'#experiencias')->assertSessionHasNoErrors();

        $experience = $user->profile->experiences()->first();
        $this->assertSame('Atendente de loja', $experience->role);
        $this->assertSame('2022-05-01', $experience->start_date->format('Y-m-d'));
        $this->assertTrue($experience->is_current);
        $this->assertNull($experience->end_date);
        $this->assertSame('manual', $experience->source);

        $this->actingAs($user)->get('/perfil')->assertSee('Atendente de loja')->assertSee('Loja Exemplo');

        $this->actingAs($user)->delete('/perfil/experiencias/'.$experience->id)->assertSessionHasNoErrors();
        $this->assertSame(0, $user->profile->experiences()->count());
    }

    public function test_experience_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/experiencias', [
            'role' => '',
            'start_month' => '2023-05',
            'end_month' => '2022-01',
        ])->assertSessionHasErrors(['role', 'end_month']);
    }

    public function test_user_can_add_and_remove_education(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/formacoes', [
            'course' => 'Técnico em Enfermagem',
            'institution' => 'Etec Exemplo',
            'level' => 'tecnico',
            'status' => 'cursando',
            'start_year' => 2025,
            'end_year' => 2026,
        ])->assertSessionHasNoErrors();

        $education = $user->profile->educations()->first();
        $this->assertSame('Técnico em Enfermagem', $education->course);
        $this->assertSame('manual', $education->source);
        $this->actingAs($user)->get('/perfil')->assertSee('Etec Exemplo');

        $this->actingAs($user)->delete('/perfil/formacoes/'.$education->id);
        $this->assertSame(0, $user->profile->educations()->count());
    }

    public function test_user_cannot_delete_someone_elses_items(): void
    {
        $other = Profile::factory()->create();
        $experience = $other->experiences()->create(['role' => 'Vendedor', 'source' => 'manual']);
        $education = $other->educations()->create(['course' => 'Administração', 'source' => 'manual']);

        $user = User::factory()->create();
        $this->actingAs($user)->delete('/perfil/experiencias/'.$experience->id)->assertNotFound();
        $this->actingAs($user)->delete('/perfil/formacoes/'.$education->id)->assertNotFound();

        $this->assertModelExists($experience);
        $this->assertModelExists($education);
    }

    public function test_completeness_grows_as_the_profile_is_filled(): void
    {
        $user = User::factory()->create();
        $empty = $user->profile()->create()->completeness();
        $this->assertSame(0, $empty['percent']);
        $this->assertContains('Cargo desejado', $empty['missing']);

        $profile = $user->profile;
        $profile->update(['desired_role' => 'Vendedor', 'area' => 'comercial', 'city' => 'Santos']);
        $profile->experiences()->create(['role' => 'Vendedor', 'source' => 'manual']);

        $filled = $profile->fresh()->completeness();
        $this->assertGreaterThan($empty['percent'], $filled['percent']);
        $this->assertNotContains('Cargo desejado', $filled['missing']);
    }
}
