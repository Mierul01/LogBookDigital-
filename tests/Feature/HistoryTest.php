<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $supervisor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Semester::factory()->current()->create();
        $this->supervisor = User::factory()->supervisor()->create(['name' => 'Dr. Lee']);
        $this->student = User::factory()->supervisedBy($this->supervisor)->create();
    }

    private function entry(array $overrides = []): array
    {
        return [
            'week_no' => 1, 'entry_date' => '2026-10-01', 'meeting_mode' => 'online', 'meeting_time' => '10:00', 'meeting_minutes' => 30,
            'progress' => 'Built the login page.', 'current_status' => 'OK', 'problem' => 'None', 'next_week_task' => 'Dashboard.',
            'student_signature' => self::SIGNATURE, ...$overrides,
        ];
    }

    public function test_the_whole_life_of_an_entry_is_recorded(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry());
        $logbook = $this->student->logbooks()->firstOrFail();

        $this->actingAs($this->supervisor)->put("/logbooks/{$logbook->id}/review", ['decision' => 'revision', 'supervisor_comment' => 'Say which login method.']);
        $this->actingAs($this->student)->put("/logbooks/{$logbook->id}", $this->entry(['student_signature' => '', 'progress' => 'Built the login page with UKM SSO.']));
        $this->actingAs($this->supervisor)->put("/logbooks/{$logbook->id}/review", ['decision' => 'approve', 'supervisor_comment' => 'Good.', 'supervisor_signature' => self::SIGNATURE]);

        $events = $logbook->fresh()->events;
        $this->assertSame(['submitted', 'sent_back', 'resubmitted', 'approved'], $events->pluck('action')->all());
        $this->assertSame('Say which login method.', $events[1]->note);
        $this->assertSame(['Built the login page.', 'Built the login page with UKM SSO.'], $events[2]->changes['progress']);
        $this->assertArrayNotHasKey('entry_date', $events[2]->changes, 'An unchanged date is not reported as a change.');

        $this->actingAs($this->student)->get("/logbooks/{$logbook->id}")->assertOk()
            ->assertSee('Fixed and resent')->assertSee('Built the login page with UKM SSO.');
    }

    public function test_saving_without_changes_adds_no_history(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry());
        $logbook = $this->student->logbooks()->firstOrFail();

        $this->actingAs($this->student)->put("/logbooks/{$logbook->id}", $this->entry(['student_signature' => '']));

        $this->assertSame(1, $logbook->events()->count());
    }

    public function test_switching_to_no_meeting_clears_the_old_meeting_details(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create(['week_no' => 1]);

        $this->actingAs($this->student)->put("/logbooks/{$logbook->id}", $this->entry(['student_signature' => '', 'meeting_mode' => 'none']))
            ->assertSessionHasNoErrors();

        $logbook->refresh();
        $this->assertNull($logbook->meeting_time);
        $this->assertNull($logbook->meeting_minutes);
    }
}
