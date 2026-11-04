<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_upload_a_photo_that_is_cropped_to_a_square_jpeg(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('eu.png', 800, 500)])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar_path;
        $this->assertStringStartsWith('avatars/'.$user->id.'-', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);

        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([400, 400, IMAGETYPE_JPEG], [$width, $height, $type]);

        $this->actingAs($user)->get('/perfil')->assertSee($path);
    }

    public function test_new_photo_replaces_the_old_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('a.jpg', 300, 300)]);
        $old = $user->fresh()->avatar_path;
        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('b.jpg', 300, 300)]);

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_invalid_files_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('avatar');
        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('mini.jpg', 50, 50)])
            ->assertSessionHasErrors('avatar');
        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('enorme.jpg', 400, 400)->size(4000)])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_user_can_remove_the_photo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('a.jpg', 300, 300)]);
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)->delete('/perfil/foto')->assertRedirect(route('profile.show'));

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_photo_is_deleted_with_the_account(): void
    {
        $user = User::factory()->create(['email' => 'ana@exemplo.com']);
        $this->actingAs($user)->post('/perfil/foto', ['avatar' => UploadedFile::fake()->image('a.jpg', 300, 300)]);
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)->delete('/conta', ['confirm_email' => 'ana@exemplo.com']);

        Storage::disk('public')->assertMissing($path);
    }
}
