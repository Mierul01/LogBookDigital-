<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private User $supervisor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-21 12:00'); // a Wednesday

        // Week 1 = 28 Sep – 4 Oct, due Wed 7 Oct. Week 3 = 12–18 Oct, due Wed 21 Oct 23:59.
        $this->semester = Semester::factory()->current()->create(['starts_on' => '2026-09-28', 'due_days' => 3]);
        $this->supervisor = User::factory()->supervisor()->create();
        $this->student = User::factory()->supervisedBy($this->supervisor)->create();
    }

    public function test_due_dates_follow_the_semester(): void
    {
        $this->assertSame('2026-10-07 23:59:59', $this->semester->dueAt(1)->format('Y-m-d H:i:s'));
        $this->assertSame(2, $this->semester->weeksPastDue()); // weeks 1 and 2; week 3 is still due tonight
    }

    public function test_an_entry_written_after_the_deadline_is_late(): void
    {
        $onTime = Logbook::factory()->for($this->student, 'student')->create(['week_no' => 3]);
        $late = Logbook::factory()->for($this->student, 'student')->create(['week_no' => 1]);

        $this->assertFalse($onTime->isLate());
        $this->assertTrue($late->isLate());
    }

    public function test_missing_weeks_past_the_deadline_are_overdue(): void
    {
        Logbook::factory()->for($this->student, 'student')->create(['week_no' => 2]);

        $this->assertSame([1], $this->student->overdueWeeks());
    }

    public function test_overdue_weeks_show_on_dashboards_and_lists(): void
    {
        $this->actingAs($this->student)->get('/dashboard')->assertOk()->assertSee('past the deadline');
        $this->actingAs($this->supervisor)->get('/dashboard')->assertOk()->assertSee('Falling behind');
        $this->actingAs($this->supervisor)->get('/students')->assertOk()->assertSee('2 weeks overdue');
        $this->actingAs(User::factory()->coordinator()->create())->get('/admin/users?role=student')->assertOk()->assertSee('2 overdue');
    }

    public function test_late_entries_are_tagged_in_the_list(): void
    {
        Logbook::factory()->for($this->student, 'student')->create(['week_no' => 1]);

        $this->actingAs($this->supervisor)->get('/logbooks')->assertOk()->assertSee('Late');
    }
}
