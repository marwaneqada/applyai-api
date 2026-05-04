<?php

namespace Tests\Feature\Resume;

use App\Domains\Resume\Actions\UploadResumeAction;
use App\Domains\Resume\Dto\UploadResumeDto;
use App\Domains\Resume\Models\Resume;
use App\Domains\Resume\Services\PdfTextExtractor;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_resume(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $file = UploadedFile::fake()->create(
            'resume.pdf',
            100,
            'application/pdf'
        );

        $response = $this->withToken($token)
            ->postJson('/api/resumes', [
                'resume' => $file,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.original_filename', 'resume.pdf')
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonPath('data.parse_status', 'failed');

        $resume = Resume::firstOrFail();

        $this->assertSame($user->id, $resume->user_id);
        Storage::disk('local')->assertExists($resume->stored_path);
    }

    public function test_upload_requires_authentication(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create(
            'resume.pdf',
            100,
            'application/pdf'
        );

        $this->postJson('/api/resumes', [
            'resume' => $file,
        ])->assertUnauthorized();
    }

    public function test_upload_requires_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $file = UploadedFile::fake()->create(
            'resume.txt',
            10,
            'text/plain'
        );

        $this->withToken($token)
            ->postJson('/api/resumes', [
                'resume' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resume']);

        $this->assertDatabaseCount('resumes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('resumes'));
    }

    public function test_user_only_sees_their_resumes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Resume::create($this->resumeAttributes($user, [
            'original_filename' => 'mine.pdf',
        ]));

        Resume::create($this->resumeAttributes($otherUser, [
            'original_filename' => 'theirs.pdf',
        ]));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson('/api/resumes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_filename', 'mine.pdf');
    }

    public function test_user_can_view_their_resume(): void
    {
        $user = User::factory()->create();

        $resume = Resume::create($this->resumeAttributes($user, [
            'original_filename' => 'resume.pdf',
        ]));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/resumes/{$resume->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $resume->id)
            ->assertJsonPath('data.original_filename', 'resume.pdf');
    }

    public function test_user_cannot_view_another_users_resume(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $resume = Resume::create($this->resumeAttributes($otherUser));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->getJson("/api/resumes/{$resume->id}")
            ->assertForbidden();
    }

    public function test_user_can_delete_their_resume_and_stored_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        Storage::disk('local')->put('resumes/resume.pdf', 'content');

        $resume = Resume::create($this->resumeAttributes($user, [
            'stored_path' => 'resumes/resume.pdf',
        ]));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->deleteJson("/api/resumes/{$resume->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('resumes', [
            'id' => $resume->id,
        ]);

        Storage::disk('local')->assertMissing('resumes/resume.pdf');
    }

    public function test_user_can_delete_resume_when_stored_file_is_missing(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $resume = Resume::create($this->resumeAttributes($user, [
            'stored_path' => 'resumes/missing.pdf',
        ]));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->deleteJson("/api/resumes/{$resume->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('resumes', [
            'id' => $resume->id,
        ]);
    }

    public function test_user_can_delete_resume_when_storage_delete_fails(): void
    {
        $user = User::factory()->create();

        $resume = Resume::create($this->resumeAttributes($user, [
            'stored_path' => 'resumes/resume.pdf',
        ]));

        $disk = Mockery::mock();
        $disk->shouldReceive('delete')
            ->once()
            ->with('resumes/resume.pdf')
            ->andThrow(new RuntimeException('Unable to reach storage.'));

        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn($disk);

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->deleteJson("/api/resumes/{$resume->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('resumes', [
            'id' => $resume->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_resume(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Storage::disk('local')->put('resumes/resume.pdf', 'content');

        $resume = Resume::create($this->resumeAttributes($otherUser, [
            'stored_path' => 'resumes/resume.pdf',
        ]));

        $this->withToken($user->createToken('api-token')->plainTextToken)
            ->deleteJson("/api/resumes/{$resume->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('resumes', [
            'id' => $resume->id,
        ]);

        Storage::disk('local')->assertExists('resumes/resume.pdf');
    }

    public function test_upload_deletes_stored_file_when_resume_record_cannot_be_created(): void
    {
        Storage::fake('local');

        $action = new UploadResumeAction(app(PdfTextExtractor::class));

        $file = UploadedFile::fake()->create(
            'resume.pdf',
            100,
            'application/pdf'
        );

        try {
            $action->execute(new UploadResumeDto(
                userId: 999,
                resume: $file,
            ));

            $this->fail('The resume record was created for a missing user.');
        } catch (QueryException) {
            $this->assertDatabaseCount('resumes', 0);
            $this->assertSame([], Storage::disk('local')->allFiles('resumes'));
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resumeAttributes(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'original_filename' => 'resume.pdf',
            'stored_path' => 'resumes/resume.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'parse_status' => 'success',
            'extracted_text' => str_repeat('Experienced developer. ', 10),
        ], $overrides);
    }
}
