<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_link_is_emailed_and_sets_a_new_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'siti@siswa.ukm.edu.my']);

        $this->get('/forgot-password')->assertOk();
        $this->post('/forgot-password', ['email' => 'siti@siswa.ukm.edu.my'])->assertSessionHasNoErrors();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get("/reset-password/{$token}?email=siti@siswa.ukm.edu.my")->assertOk();
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'siti@siswa.ukm.edu.my', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@ukm.edu.my'])->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertNothingSent();
    }

    public function test_a_bad_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'ali@siswa.ukm.edu.my']);

        $this->post('/reset-password', [
            'token' => 'wrong', 'email' => 'ali@siswa.ukm.edu.my', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
