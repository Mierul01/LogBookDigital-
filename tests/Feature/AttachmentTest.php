<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $supervisor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Semester::factory()->current()->create();
        $this->supervisor = User::factory()->supervisor()->create();
        $this->student = User::factory()->supervisedBy($this->supervisor)->create();
    }

    private function entry(array $overrides = []): array
    {
        return [
            'week_no' => 1, 'entry_date' => now()->toDateString(), 'meeting_mode' => 'none',
            'progress' => 'Built the API.', 'current_status' => 'OK', 'problem' => 'None', 'next_week_task' => 'Tests.',
            'student_signature' => self::SIGNATURE, ...$overrides,
        ];
    }

    public function test_student_attaches_files_and_the_supervisor_downloads_them(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry([
            'attachments' => [UploadedFile::fake()->create('erd.pdf', 200, 'application/pdf'), UploadedFile::fake()->image('screen.png')],
        ]))->assertSessionHasNoErrors();

        $logbook = $this->student->logbooks()->firstOrFail();
        $this->assertCount(2, $logbook->attachments);
        $file = $logbook->attachments->firstWhere('original_name', 'erd.pdf');
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($this->supervisor)->get("/logbooks/{$logbook->id}")->assertSee('erd.pdf');
        $this->actingAs($this->supervisor)->get("/logbooks/{$logbook->id}/attachments/{$file->id}")
            ->assertOk()->assertDownload('erd.pdf');
    }

    public function test_other_people_cannot_download(): void
    {
        $logbook = Logbook::factory()->for($this->student, 'student')->create();
        $file = $logbook->attachments()->create(['original_name' => 'a.pdf', 'path' => 'attachments/x/a.pdf', 'size' => 10]);

        $this->actingAs(User::factory()->create())->get("/logbooks/{$logbook->id}/attachments/{$file->id}")->assertForbidden();
        $this->actingAs(User::factory()->supervisor()->create())->get("/logbooks/{$logbook->id}/attachments/{$file->id}")->assertForbidden();
    }

    public function test_dangerous_file_types_are_rejected(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry([
            'attachments' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]))->assertSessionHasErrors('attachments.0');
    }

    public function test_removing_a_file_on_edit_deletes_it(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry([
            'attachments' => [UploadedFile::fake()->create('old.pdf', 20, 'application/pdf')],
        ]));
        $logbook = $this->student->logbooks()->firstOrFail();
        $file = $logbook->attachments()->firstOrFail();

        $this->actingAs($this->student)->put("/logbooks/{$logbook->id}", $this->entry([
            'student_signature' => '', 'remove_attachments' => [$file->id],
        ]))->assertSessionHasNoErrors();

        $this->assertModelMissing($file);
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_an_entry_holds_at_most_five_files(): void
    {
        $files = collect(range(1, 6))->map(fn ($i) => UploadedFile::fake()->create("f{$i}.pdf", 5, 'application/pdf'))->all();

        $this->actingAs($this->student)->post('/logbooks', $this->entry(['attachments' => $files]))
            ->assertSessionHasErrors('attachments');
    }

    public function test_deleting_the_entry_deletes_its_files(): void
    {
        $this->actingAs($this->student)->post('/logbooks', $this->entry([
            'attachments' => [UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')],
        ]));
        $logbook = $this->student->logbooks()->firstOrFail();
        $path = $logbook->attachments()->value('path');

        $this->actingAs($this->student)->delete("/logbooks/{$logbook->id}");

        Storage::disk('local')->assertMissing($path);
    }
}
