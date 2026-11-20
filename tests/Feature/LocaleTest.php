<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_switch_remembers_bahasa_melayu(): void
    {
        $this->from('/login')->get('/locale/ms')
            ->assertRedirect('/login')
            ->assertCookie('locale', 'ms', encrypted: false);
    }

    public function test_pages_show_in_bahasa_melayu(): void
    {
        $this->withUnencryptedCookie('locale', 'ms')->get('/login')
            ->assertOk()->assertSee('Selamat kembali')->assertSee('Log masuk');

        Semester::factory()->current()->create();
        $supervisor = User::factory()->supervisor()->create();
        $logbook = Logbook::factory()->for(User::factory()->supervisedBy($supervisor), 'student')->create();

        $this->withUnencryptedCookie('locale', 'ms')->actingAs($supervisor)->get("/logbooks/{$logbook->id}")
            ->assertOk()->assertSee('Semakan penyelia')->assertSee('Menunggu')->assertSee('Hantar balik');
    }

    public function test_english_is_the_default_and_unknown_languages_are_refused(): void
    {
        $this->get('/login')->assertSee('Welcome back');
        $this->get('/locale/fr')->assertNotFound();
    }
}
