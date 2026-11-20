<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogbookTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $supervisor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Semester::factory()->current()->create();
        $this->supervisor = User::factory()->supervisor()->create();
        $this->student = User::factory()->supervisedBy($this->supervisor)->create();
    }

    private function entry(array $overrides = []): array
    {
        return [
            'week_no' => 1,
            'entry_date' => '2026-09-01',
            'progress' => 'Built the login page.',
            'current_status' => 'On track.',
            'problem' => 'None.',
            'next_week_task' => 'Build the dashboard.',
            'student_signature' => self::SIGNATURE,
            ...$overrides,
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_student_can_log_in_with_lowercase_matric_number(): void
    {
        $this->post('/login', ['username' => strtolower($this->student->username), 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($this->student);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/login', ['username' => $this->student->username, 'password' => 'nope'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_student_can_create_an_entry(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry())->assertRedirect();

        $this->assertDatabaseHas('logbooks', ['student_id' => $this->student->id, 'week_no' => 1]);
    }

    public function test_student_cannot_log_the_same_week_twice(): void
    {
        Logbook::factory()->for($this->student, 'student')->create(['week_no' => 1]);

        $this->actingAs($this->student)->post('/logbooks', $this->entry())->assertSessionHasErrors('week_no');
    }

    public function test_supervisor_cannot_create_entries(): void
    {
        $this->actingAs($this->supervisor)->post('/logbooks', $this->entry())->assertForbidden();
    }

    public function test_supervisor_can_review_their_students_entry(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();

        $this->actingAs($this->supervisor)
            ->put("/logbooks/{$logbook->id}/review", ['supervisor_comment' => 'Good.', 'supervisor_signature' => self::SIGNATURE])
            ->assertRedirect();

        $this->assertTrue($logbook->fresh()->isReviewed());
    }

    public function test_review_requires_a_signature(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();

        $this->actingAs($this->supervisor)
            ->put("/logbooks/{$logbook->id}/review", ['decision' => 'approve', 'supervisor_comment' => 'Good.', 'supervisor_signature' => ''])
            ->assertSessionHasErrors('supervisor_signature');
    }

    public function test_other_supervisors_cannot_see_or_review_the_entry(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();
        $other = User::factory()->supervisor()->create();

        $this->actingAs($other)->get("/logbooks/{$logbook->id}")->assertForbidden();
        $this->actingAs($other)
            ->put("/logbooks/{$logbook->id}/review", ['supervisor_comment' => 'x', 'supervisor_signature' => self::SIGNATURE])
            ->assertForbidden();
    }

    public function test_students_cannot_touch_a_classmates_entries(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();
        $classmate = User::factory()->supervisedBy($this->supervisor)->create();

        $this->actingAs($classmate)->get("/logbooks/{$logbook->id}")->assertForbidden();
        $this->actingAs($classmate)->delete("/logbooks/{$logbook->id}")->assertForbidden();
    }

    public function test_approved_entry_is_locked_for_editing_and_deleting(): void
    {
        $logbook = Logbook::factory()->reviewed()->for($this->student, 'student')->create();

        $this->actingAs($this->student)->put("/logbooks/{$logbook->id}", $this->entry())->assertForbidden();
        $this->actingAs($this->student)->delete("/logbooks/{$logbook->id}")->assertForbidden();
        $this->assertModelExists($logbook);
    }

    public function test_student_must_sign_a_new_entry(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry(['student_signature' => '']))
            ->assertSessionHasErrors('student_signature');
    }

    public function test_submitting_notifies_the_supervisor(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry());

        $this->assertSame(1, $this->supervisor->unreadNotifications()->count());
    }

    public function test_supervisor_can_send_an_entry_back_without_signing(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();

        $this->actingAs($this->supervisor)
            ->put("/logbooks/{$logbook->id}/review", ['decision' => 'revision', 'supervisor_comment' => 'Add more detail.'])
            ->assertSessionHasNoErrors();

        $logbook->refresh();
        $this->assertTrue($logbook->needsRevision());
        $this->assertNull($logbook->supervisor_signature);
        $this->assertSame(1, $this->student->unreadNotifications()->count());
    }

    public function test_fixing_a_sent_back_entry_resubmits_it(): void
    {
        $logbook = Logbook::factory()->needsRevision()->for($this->student, 'student')->create(['week_no' => 1]);

        $this->actingAs($this->student)
            ->put("/logbooks/{$logbook->id}", $this->entry(['student_signature' => '', 'problem' => 'Server keeps timing out.']))
            ->assertSessionHasNoErrors();

        $logbook->refresh();
        $this->assertTrue($logbook->isPending());
        $this->assertSame('Server keeps timing out.', $logbook->problem);
        $this->assertNotNull($logbook->student_signature, 'An empty signature on edit keeps the old one.');
        $this->assertSame(1, $this->supervisor->unreadNotifications()->count());
    }

    public function test_opening_a_notification_marks_it_read(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry());
        $note = $this->supervisor->notifications()->first();

        $this->actingAs($this->supervisor)->get("/notifications/{$note->id}")
            ->assertRedirect("/logbooks/{$note->data['logbook_id']}");

        $this->assertNotNull($note->fresh()->read_at);
    }

    public function test_full_logbook_report(): void
    {
        Logbook::factory()->reviewed()->for($this->student, 'student')->create(['week_no' => 2, 'progress' => 'Drew the ERD.']);
        $other = User::factory()->supervisor()->create();

        $this->actingAs($this->student)->get('/logbooks/report')->assertOk()->assertSee('Drew the ERD.');
        $this->actingAs($this->supervisor)->get("/logbooks/report?student={$this->student->id}")->assertOk()->assertSee('Drew the ERD.');
        $this->actingAs($other)->get("/logbooks/report?student={$this->student->id}")->assertNotFound();
    }
    public function test_every_page_renders(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();

        foreach (['/dashboard', '/logbooks', '/logbooks/create', "/logbooks/{$logbook->id}", "/logbooks/{$logbook->id}/edit", '/profile'] as $url) {
            $this->actingAs($this->student)->get($url)->assertOk();
        }

        foreach (['/dashboard', '/logbooks', "/logbooks/{$logbook->id}", '/students', '/students/create', "/students/{$this->student->id}/edit", '/profile'] as $url) {
            $this->actingAs($this->supervisor)->get($url)->assertOk();
        }
    }
}
