<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_register_a_student(): void
    {
        $supervisor = User::factory()->supervisor()->create();

        $this->actingAs($supervisor)->post('/students', [
            'username' => 'a100001',
            'name' => 'Ali Abu',
            'password' => 'secret123',
        ])->assertRedirect('/students');

        $student = User::where('username', 'A100001')->firstOrFail();
        $this->assertTrue($student->isStudent());
        $this->assertSame($supervisor->id, $student->supervisor_id);
        $this->assertTrue(Hash::check('secret123', $student->password));
    }

    public function test_blank_password_on_update_keeps_the_old_one(): void
    {
        $supervisor = User::factory()->supervisor()->create();
        $student = User::factory()->supervisedBy($supervisor)->create();

        $this->actingAs($supervisor)->put("/students/{$student->id}", [
            'username' => $student->username,
            'name' => 'Renamed',
            'password' => '',
        ])->assertRedirect('/students');

        $this->assertTrue(Hash::check('password', $student->fresh()->password));
        $this->assertSame('Renamed', $student->fresh()->name);
    }

    public function test_students_cannot_access_student_management(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/students')->assertForbidden();
    }

    public function test_supervisor_cannot_manage_another_supervisors_student(): void
    {
        $student = User::factory()->supervisedBy(User::factory()->supervisor()->create())->create();

        $this->actingAs(User::factory()->supervisor()->create())
            ->delete("/students/{$student->id}")
            ->assertNotFound();

        $this->assertModelExists($student);
    }

    public function test_supervisor_can_self_register(): void
    {
        $this->post('/register', [
            'name' => 'Dr. Lee',
            'username' => 'drlee',
            'email' => 'lee@ukm.edu.my',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->assertTrue(User::where('username', 'drlee')->first()->isSupervisor());
    }
}
