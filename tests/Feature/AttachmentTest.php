<?php

namespace Tests\Feature;

use App\Mcp\Servers\RelayServer;
use App\Mcp\Tools\AttachFileTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ReadAttachmentTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->owner = User::factory()->create();
        $this->dev = User::factory()->create();

        $this->actingAs($this->owner)->postJson('/api/projects', ['name' => 'Website Relaunch'])->assertCreated();
        $this->postJson('/api/projects/WR/members', ['email' => $this->dev->email, 'role' => 'member'])->assertCreated();
        $this->postJson('/api/tasks', ['project' => 'WR', 'title' => 'Build the pricing page', 'assignee_id' => $this->dev->id])->assertCreated();
    }

    private function upload(User $as, string $url, UploadedFile $file)
    {
        return $this->actingAs($as)->post($url, ['file' => $file], ['Accept' => 'application/json']);
    }

    private function storedFiles(): int
    {
        return count(Storage::disk('local')->allFiles('attachments'));
    }

    public function test_a_text_file_on_a_task_is_readable_by_the_assistant(): void
    {
        $file = UploadedFile::fake()->createWithContent('spec.md', "# Pricing spec\n\nThe primary button must be teal.");
        $attachment = $this->upload($this->dev, '/api/tasks/WR-1/attachments', $file)
            ->assertCreated()
            ->assertJsonPath('data.kind', 'text')
            ->assertJsonPath('data.has_text', true)
            ->json('data');

        $this->assertSame(1, $this->storedFiles());

        $task = $this->getJson('/api/tasks/WR-1')->assertOk()->json('data');
        $this->assertSame(1, $task['attachment_count']);
        $this->assertSame('spec.md', $task['attachments'][0]['name']);
        $this->assertContains('attached', array_column($task['activity'], 'type'));

        RelayServer::actingAs($this->dev)->tool(GetTaskTool::class, ['task' => 'WR-1'])->assertOk()->assertSee('spec.md');
        RelayServer::actingAs($this->dev)->tool(ReadAttachmentTool::class, ['attachment_id' => $attachment['id']])
            ->assertOk()
            ->assertSee('The primary button must be teal.')
            ->assertSee('End of file');
    }

    public function test_long_text_is_paged(): void
    {
        $id = $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('log.txt', str_repeat("line of the log\n", 2000)))
            ->assertCreated()->json('data.id');

        RelayServer::actingAs($this->dev)->tool(ReadAttachmentTool::class, ['attachment_id' => $id, 'max_chars' => 1000])
            ->assertOk()
            ->assertSee('offset=1000');
    }

    public function test_word_documents_are_turned_into_text(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('word/document.xml', '<w:document xmlns:w="w"><w:body><w:p><w:r><w:t>Hello from Word</w:t></w:r></w:p><w:p><w:r><w:t>مرحبا بالعالم</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        $id = $this->upload($this->dev, '/api/tasks/WR-1/attachments', new UploadedFile($path, 'brief.docx', null, null, true))
            ->assertCreated()
            ->assertJsonPath('data.kind', 'document')
            ->json('data.id');

        RelayServer::actingAs($this->dev)->tool(ReadAttachmentTool::class, ['attachment_id' => $id])
            ->assertOk()
            ->assertSee('Hello from Word')
            ->assertSee('مرحبا بالعالم');
    }

    public function test_images_are_sent_to_the_assistant_as_images(): void
    {
        $id = $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->image('screenshot.png', 40, 30))
            ->assertCreated()
            ->assertJsonPath('data.kind', 'image')
            ->json('data.id');

        RelayServer::actingAs($this->dev)->tool(ReadAttachmentTool::class, ['attachment_id' => $id])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Image “screenshot.png”');
    }

    public function test_project_files_are_listed_for_the_assistant(): void
    {
        $this->upload($this->owner, '/api/projects/WR/attachments', UploadedFile::fake()->createWithContent('brand-guide.md', 'Use teal.'))
            ->assertCreated()
            ->assertJsonPath('data.task_id', null);

        $this->getJson('/api/projects/WR/attachments')->assertOk()->assertJsonCount(1, 'data')->assertJsonStructure(['limits' => ['max_bytes', 'extensions']]);
        RelayServer::actingAs($this->dev)->tool(GetProjectTool::class, ['project' => 'WR'])->assertOk()->assertSee('brand-guide.md');
    }

    public function test_unsafe_or_disguised_files_are_rejected(): void
    {
        $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('logo.svg', '<svg onload="alert(1)"/>'))
            ->assertUnprocessable()->assertJsonValidationErrors('file');

        $image = UploadedFile::fake()->image('x.png', 10, 10); // keep a reference: the temp file dies with the object
        $png = file_get_contents($image->getRealPath());
        $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('notes.txt', $png))
            ->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertSame(0, $this->storedFiles());
    }

    public function test_outsiders_cannot_reach_files(): void
    {
        $id = $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('secret.txt', 'top secret'))->json('data.id');
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get("/api/attachments/{$id}/download")->assertNotFound();
        $this->actingAs($stranger)->getJson('/api/tasks/WR-1/attachments')->assertNotFound();
        $this->upload($stranger, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('x.txt', 'x'))->assertNotFound();
        RelayServer::actingAs($stranger)->tool(ReadAttachmentTool::class, ['attachment_id' => $id])->assertHasErrors(['not found']);
    }

    public function test_downloads_are_forced_attachments_with_nosniff(): void
    {
        $id = $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('notes.txt', 'hello'))->json('data.id');

        $response = $this->get("/api/attachments/{$id}/download?inline=1")->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('hello', $response->streamedContent());
    }

    public function test_only_the_uploader_or_an_admin_can_delete_and_the_file_is_removed(): void
    {
        $other = User::factory()->create();
        $this->actingAs($this->owner)->postJson('/api/projects/WR/members', ['email' => $other->email, 'role' => 'member']);
        $id = $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('a.txt', 'a'))->json('data.id');

        $this->actingAs($other)->deleteJson("/api/attachments/{$id}")->assertForbidden();
        $this->actingAs($this->owner)->deleteJson("/api/attachments/{$id}")->assertNoContent();
        $this->assertSame(0, $this->storedFiles());
    }

    public function test_the_assistant_can_attach_its_own_report_to_a_task(): void
    {
        RelayServer::actingAs($this->dev)->tool(AttachFileTool::class, [
            'task' => 'WR-1',
            'filename' => 'pricing-research',
            'content' => "# Competitor pricing\n\nMost tools charge per seat. مقارنة الأسعار مرفقة.",
        ])->assertOk()->assertSee('pricing-research.md');

        $file = $this->actingAs($this->dev)->getJson('/api/tasks/WR-1')->json('data.attachments.0');
        $this->assertSame('pricing-research.md', $file['name']);
        $this->assertSame('mcp', $file['channel']);
        $this->assertSame(1, $this->storedFiles());

        // A later conversation can read it back, Arabic intact.
        RelayServer::actingAs($this->dev)->tool(ReadAttachmentTool::class, ['attachment_id' => $file['id']])
            ->assertOk()->assertSee('Most tools charge per seat.')->assertSee('مقارنة الأسعار مرفقة.');
    }

    public function test_the_assistant_can_attach_project_files_but_only_text_and_only_where_allowed(): void
    {
        RelayServer::actingAs($this->dev)->tool(AttachFileTool::class, ['project' => 'WR', 'filename' => 'leads.csv', 'content' => "name,email\nSam,sam@example.com"])
            ->assertOk();
        $this->assertSame('leads.csv', $this->actingAs($this->dev)->getJson('/api/projects/WR/attachments')->json('data.0.name'));

        RelayServer::actingAs($this->dev)->tool(AttachFileTool::class, ['task' => 'WR-1', 'filename' => 'evil.html', 'content' => '<script>alert(1)</script>'])
            ->assertHasErrors(['text files only']);
        RelayServer::actingAs($this->dev)->tool(AttachFileTool::class, ['task' => 'WR-1', 'project' => 'WR', 'filename' => 'a.md', 'content' => 'x'])
            ->assertHasErrors();
        RelayServer::actingAs($this->dev)->tool(AttachFileTool::class, ['task' => 'WR-1', 'filename' => 'big.txt', 'content' => str_repeat('a', AttachFileTool::MAX_BYTES + 1)])
            ->assertHasErrors();
        RelayServer::actingAs(User::factory()->create())->tool(AttachFileTool::class, ['task' => 'WR-1', 'filename' => 'a.md', 'content' => 'x'])
            ->assertHasErrors(['not found']);

        $this->assertSame(1, $this->storedFiles());
    }

    public function test_deleting_a_task_or_project_removes_its_files(): void
    {
        $this->upload($this->dev, '/api/tasks/WR-1/attachments', UploadedFile::fake()->createWithContent('a.txt', 'a'));
        $this->actingAs($this->owner)->deleteJson('/api/tasks/WR-1')->assertNoContent();
        $this->assertSame(0, $this->storedFiles());

        $this->upload($this->owner, '/api/projects/WR/attachments', UploadedFile::fake()->createWithContent('b.txt', 'b'));
        $this->actingAs($this->owner)->deleteJson('/api/projects/WR')->assertNoContent();
        $this->assertSame(0, $this->storedFiles());
    }
}
