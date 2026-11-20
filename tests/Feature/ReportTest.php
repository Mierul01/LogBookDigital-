<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Semester::factory()->current()->create();
        $this->supervisor = User::factory()->supervisor()->create(['name' => 'Dr. Lee']);
        $this->student = User::factory()->supervisedBy($this->supervisor)->create(['name' => 'Siti Aminah', 'username' => 'A100001']);
        Logbook::factory()->reviewed()->for($this->student, 'student')->create(['week_no' => 1]);
    }

    public function test_supervisor_sees_only_their_students(): void
    {
        User::factory()->supervisedBy(User::factory()->supervisor()->create())->create(['name' => 'Other Student']);

        $this->actingAs($this->supervisor)->get('/reports')->assertOk()
            ->assertSee('Siti Aminah')->assertDontSee('Other Student')->assertDontSee('By supervisor');
    }

    public function test_coordinator_sees_the_supervisor_summary(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())->get('/reports')->assertOk()
            ->assertSee('By supervisor')->assertSee('Dr. Lee')->assertSee('Siti Aminah');
    }

    public function test_student_progress_exports_as_csv(): void
    {
        $response = $this->actingAs($this->supervisor)->get('/reports/students.csv')->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('A100001,"Siti Aminah","Dr. Lee"', $csv);
    }

    public function test_csv_cells_cannot_run_as_formulas(): void
    {
        $this->student->update(['name' => '=HYPERLINK("http://evil")']);

        $csv = $this->actingAs($this->supervisor)->get('/reports/students.csv')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_only_the_coordinator_exports_the_supervisor_summary(): void
    {
        $this->actingAs($this->supervisor)->get('/reports/supervisors.csv')->assertForbidden();

        $csv = $this->actingAs(User::factory()->coordinator()->create())->get('/reports/supervisors.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Dr. Lee', $csv);
    }

    public function test_students_cannot_open_reports(): void
    {
        $this->actingAs($this->student)->get('/reports')->assertForbidden();
    }
}
