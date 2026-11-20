<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinator = User::factory()->coordinator()->create();
    }

    public function test_coordinator_creates_a_supervisor_and_a_student_assigned_to_them(): void
    {
        $this->actingAs($this->coordinator)->post('/admin/users', [
            'role' => 'supervisor', 'username' => 'dr.lee', 'name' => 'Dr. Lee', 'email' => 'lee@ukm.edu.my', 'password' => 'password123',
        ])->assertSessionHasNoErrors();

        $supervisor = User::where('username', 'dr.lee')->firstOrFail();
        $this->assertTrue($supervisor->isSupervisor());

        $this->actingAs($this->coordinator)->post('/admin/users', [
            'role' => 'student', 'username' => 'a200001', 'name' => 'Siti', 'password' => 'password123', 'supervisor_id' => $supervisor->id,
        ])->assertSessionHasNoErrors();

        $student = User::where('username', 'A200001')->firstOrFail();
        $this->assertTrue($student->supervisor->is($supervisor));
    }

    public function test_a_student_cannot_be_assigned_to_a_non_supervisor(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->coordinator)->post('/admin/users', [
            'role' => 'student', 'username' => 'A200002', 'name' => 'Ali', 'password' => 'password123', 'supervisor_id' => $other->id,
        ])->assertSessionHasErrors('supervisor_id');
    }

    public function test_coordinator_can_reassign_a_student_and_keep_the_password(): void
    {
        $old = User::factory()->supervisor()->create();
        $new = User::factory()->supervisor()->create();
        $student = User::factory()->supervisedBy($old)->create();
        $hash = $student->password;

        $this->actingAs($this->coordinator)->put("/admin/users/{$student->id}", [
            'role' => 'student', 'username' => $student->username, 'name' => $student->name, 'supervisor_id' => $new->id, 'password' => '',
        ])->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame($new->id, $student->supervisor_id);
        $this->assertSame($hash, $student->password);
    }

    public function test_coordinator_sees_every_logbook_but_cannot_review(): void
    {
        $supervisor = User::factory()->supervisor()->create();
        $logbook = Logbook::factory()->for(User::factory()->supervisedBy($supervisor), 'student')->create(['progress' => 'Built the API.']);

        $this->actingAs($this->coordinator)->get('/logbooks')->assertOk()->assertSee('Built the API.');
        $this->actingAs($this->coordinator)->get("/logbooks/{$logbook->id}")->assertOk();
        $this->actingAs($this->coordinator)->get("/logbooks/report?student={$logbook->student_id}")->assertOk();
        $this->actingAs($this->coordinator)
            ->put("/logbooks/{$logbook->id}/review", ['decision' => 'revision', 'supervisor_comment' => 'x'])
            ->assertForbidden();
    }

    public function test_coordinator_cannot_delete_themself(): void
    {
        $this->actingAs($this->coordinator)->delete("/admin/users/{$this->coordinator->id}")->assertStatus(422);
        $this->assertModelExists($this->coordinator);
    }

    public function test_only_coordinators_reach_user_management(): void
    {
        $supervisor = User::factory()->supervisor()->create();

        $this->actingAs($supervisor)->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_coordinator_pages_render(): void
    {
        $student = User::factory()->create();

        foreach (['/dashboard', '/logbooks', '/admin/users', '/admin/users?role=student', '/admin/users/create', "/admin/users/{$student->id}/edit", '/profile'] as $url) {
            $this->actingAs($this->coordinator)->get($url)->assertOk();
        }
    }
}
