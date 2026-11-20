<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinator = User::factory()->coordinator()->create();
        User::factory()->supervisor()->create(['username' => 'dr.aisyah']);
    }

    private function upload(string $csv)
    {
        return $this->actingAs($this->coordinator)->post('/admin/users/import', [
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);
    }

    public function test_students_are_imported_and_assigned_to_their_supervisor(): void
    {
        $this->upload("username,name,email,role,supervisor,password\na192920,Siti Aminah,siti@siswa.ukm.edu.my,student,DR.AISYAH,secret123\nA192921,Ali Bakar,,,dr.aisyah,\n")
            ->assertOk()->assertSee('2 of 2 rows imported');

        $siti = User::where('username', 'A192920')->firstOrFail();
        $this->assertSame('dr.aisyah', $siti->supervisor->username);
        $this->assertTrue(Hash::check('secret123', $siti->password));
        $this->assertTrue(User::where('username', 'A192921')->firstOrFail()->isStudent());
    }

    public function test_bad_rows_are_skipped_with_a_reason_and_good_rows_still_import(): void
    {
        $this->upload("username,name,supervisor\nA1,Good Student,dr.aisyah\nA2,,dr.aisyah\nA3,Lost Student,dr.nobody\nA1,Twice,\n")
            ->assertOk()
            ->assertSee('1 of 4 rows imported')
            ->assertSee('Line 3')
            ->assertSee('dr.nobody')
            ->assertSee('Line 5');

        $this->assertSame(1, User::where('role', 'student')->count());
    }

    public function test_malay_headers_from_excel_are_understood(): void
    {
        $this->upload("\xEF\xBB\xBFNo Matrik,Nama,Emel,Penyelia\nA300001,Nur Aisyah,,dr.aisyah\n")->assertOk();

        $this->assertSame('Nur Aisyah', User::where('username', 'A300001')->value('name'));
    }

    public function test_the_template_downloads_and_only_coordinators_import(): void
    {
        $this->actingAs($this->coordinator)->get('/admin/users/import/template.csv')->assertOk();
        $this->actingAs($this->coordinator)->get('/admin/users/import')->assertOk();

        $this->actingAs(User::where('username', 'dr.aisyah')->first())->get('/admin/users/import')->assertForbidden();
    }
}
