<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemesterTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private function entry(int $week = 1): array
    {
        return [
            'week_no' => $week, 'entry_date' => now()->toDateString(), 'progress' => 'Did things.', 'current_status' => 'OK',
            'problem' => 'None', 'next_week_task' => 'More things.', 'student_signature' => self::SIGNATURE,
            'meeting_mode' => 'online', 'meeting_time' => '14:30', 'meeting_minutes' => 45,
        ];
    }

    public function test_coordinator_adds_a_semester_and_makes_it_current(): void
    {
        $old = Semester::factory()->current()->create();
        $coordinator = User::factory()->coordinator()->create();

        $this->actingAs($coordinator)->post('/admin/semesters', [
            'name' => 'Semester 2 2026/2027', 'starts_on' => '2027-03-01', 'is_current' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($old->fresh()->is_current);
        $this->assertSame('Semester 2 2026/2027', Semester::current()->name);
    }

    public function test_week_one_can_be_logged_again_in_a_new_semester(): void
    {
        $student = User::factory()->supervisedBy(User::factory()->supervisor()->create())->create();
        $first = Semester::factory()->current()->create();
        Logbook::factory()->for($student, 'student')->create(['week_no' => 1, 'semester_id' => $first->id]);

        // Same semester: week 1 is taken.
        $this->actingAs($student)->post('/logbooks', $this->entry())->assertSessionHasErrors('week_no');

        Semester::factory()->create()->makeCurrent();

        $this->actingAs($student)->post('/logbooks', $this->entry())->assertSessionHasNoErrors();
        $this->assertSame(2, $student->logbooks()->where('week_no', 1)->count());
        $this->assertSame(2, $student->nextLogbookWeek());
    }

    public function test_a_semester_with_entries_cannot_be_deleted(): void
    {
        $semester = Semester::factory()->create();
        Logbook::factory()->create(['semester_id' => $semester->id]);
        Semester::factory()->current()->create();

        $this->actingAs(User::factory()->coordinator()->create())
            ->delete("/admin/semesters/{$semester->id}")->assertStatus(422);
    }

    public function test_student_saves_a_project_title_shown_on_the_printout(): void
    {
        Semester::factory()->current()->create();
        $student = User::factory()->create();

        $this->actingAs($student)->put('/profile/project', ['title' => 'Smart parking for UKM', 'stage' => 'FYP2'])
            ->assertSessionHasNoErrors();

        $this->assertSame('FYP2', $student->project()->stage);
        $this->actingAs($student)->get('/logbooks/report')->assertOk()->assertSee('Smart parking for UKM');
    }

    public function test_supervisors_cannot_save_a_project(): void
    {
        Semester::factory()->current()->create();

        $this->actingAs(User::factory()->supervisor()->create())
            ->put('/profile/project', ['title' => 'x', 'stage' => 'FYP1'])->assertForbidden();
    }

    public function test_students_cannot_write_before_a_semester_is_open(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/logbooks/create')->assertRedirect('/logbooks');
        $this->actingAs($student)->post('/logbooks', $this->entry())->assertRedirect('/logbooks');
        $this->assertSame(0, Logbook::count());
    }

    public function test_semester_pages_render(): void
    {
        $semester = Semester::factory()->current()->create();
        $coordinator = User::factory()->coordinator()->create();

        foreach (['/admin/semesters', '/admin/semesters/create', "/admin/semesters/{$semester->id}/edit"] as $url) {
            $this->actingAs($coordinator)->get($url)->assertOk();
        }
    }
}
