<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\DeadlineReminder;
use App\Notifications\LogbookActivity;
use App\Notifications\PendingReviewDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_results_are_emailed_to_students_with_an_address(): void
    {
        Notification::fake();
        Semester::factory()->current()->create();
        $supervisor = User::factory()->supervisor()->create();
        $student = User::factory()->supervisedBy($supervisor)->create(['email' => 'siti@siswa.ukm.edu.my']);
        $logbook = Logbook::factory()->for($student, 'student')->create();

        $this->actingAs($supervisor)->put("/logbooks/{$logbook->id}/review", ['decision' => 'revision', 'supervisor_comment' => 'Add detail.']);

        Notification::assertSentTo($student, LogbookActivity::class, fn ($n, array $channels) => in_array('mail', $channels));
    }

    public function test_no_email_when_turned_off_or_missing(): void
    {
        $off = User::factory()->create(['email' => 'a@siswa.ukm.edu.my', 'email_notifications' => false]);
        $none = User::factory()->create(['email' => null]);

        $this->assertFalse($off->wantsEmail());
        $this->assertFalse($none->wantsEmail());
    }

    public function test_profile_toggle_turns_emails_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => $user->email, 'email_notifications' => '0'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->email_notifications);
    }

    public function test_students_missing_todays_week_get_a_reminder(): void
    {
        Notification::fake();
        $this->travelTo('2026-10-21 08:00'); // Wednesday: week 3 is due tonight
        $semester = Semester::factory()->current()->create(['starts_on' => '2026-09-28', 'due_days' => 3]);
        $supervisor = User::factory()->supervisor()->create();
        $done = User::factory()->supervisedBy($supervisor)->create();
        $missing = User::factory()->supervisedBy($supervisor)->create();
        Logbook::factory()->for($done, 'student')->create(['week_no' => 3, 'semester_id' => $semester->id]);

        $this->artisan('logbook:remind')->assertSuccessful();

        Notification::assertSentTo($missing, DeadlineReminder::class);
        Notification::assertNotSentTo($done, DeadlineReminder::class);
    }

    public function test_supervisors_get_a_summary_of_pending_entries(): void
    {
        Notification::fake();
        Semester::factory()->current()->create();
        $busy = User::factory()->supervisor()->create();
        $idle = User::factory()->supervisor()->create();
        Logbook::factory()->for(User::factory()->supervisedBy($busy), 'student')->create();

        $this->artisan('logbook:remind --digest')->assertSuccessful();

        Notification::assertSentTo($busy, PendingReviewDigest::class);
        Notification::assertNotSentTo($idle, PendingReviewDigest::class);
    }
}
