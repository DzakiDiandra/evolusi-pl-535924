<?php

namespace Tests\Feature;

use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TugasApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_json_list(): void
    {
        Tugas::factory()->count(3)->create();
        $this->getJson('/api/tugas')->assertOk()->assertJsonCount(3);
    }

    public function test_store_requires_login(): void
    {
        $this->postJson('/api/tugas', ['judul' => 'x'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/tugas', ['judul' => 'Belajar Docker'])
            ->assertCreated()->assertJsonFragment(['judul' => 'Belajar Docker']);
    }

    public function test_validation_fails_without_title(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/tugas', [])->assertStatus(422);
    }
}
