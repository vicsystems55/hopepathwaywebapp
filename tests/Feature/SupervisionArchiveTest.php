<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupervisionArchiveStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupervisionArchiveTest extends TestCase
{
    private const ID = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const BASE = '/api/admin/supervision-archives';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_archive_routes_require_authentication_and_the_admin_role(): void
    {
        $this->getJson(self::BASE)->assertUnauthorized();
        $this->getJson(self::BASE . '/' . self::ID)->assertUnauthorized();
        $this->getJson(self::BASE . '/' . self::ID . '/sessions/10')->assertUnauthorized();
        $this->postJson(self::BASE . '/preview')->assertUnauthorized();
        $this->postJson(self::BASE)->assertUnauthorized();

        // A staff account cannot use the archive even if its permission list is expanded.
        config(['access.roles.staff' => ['supervision.manage']]);
        $this->signIn('staff');
        $this->getJson(self::BASE)->assertForbidden();
        $this->getJson(self::BASE . '/' . self::ID)->assertForbidden();
        $this->getJson(self::BASE . '/' . self::ID . '/sessions/10')->assertForbidden();
        $this->postJson(self::BASE . '/preview')->assertForbidden();
        $this->postJson(self::BASE)->assertForbidden();
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->signIn('admin');
        config(['access.roles.admin' => []]);
        $this->getJson(self::BASE)->assertForbidden();
    }

    public function test_preview_has_no_storage_side_effect_and_import_is_private_and_idempotent(): void
    {
        $this->signIn('admin');
        $archive = $this->fixture();
        $preview = $this->upload(self::BASE . '/preview', $archive)->assertOk()
            ->assertJsonPath('data.counts.completed_sessions', 2)
            ->assertJsonPath('data.existing', false);
        $this->assertArrayNotHasKey('sessions', $preview->json('data'));
        $this->assertArrayNotHasKey('questions', $preview->json('data'));
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->upload(self::BASE, $archive)->assertCreated()
            ->assertJsonPath('data.id', self::ID)
            ->assertJsonPath('data.created', true);
        Storage::disk('local')->assertExists('supervision-archives/' . self::ID . '.json');
        Storage::disk('public')->assertMissing('supervision-archives/' . self::ID . '.json');
        $this->upload(self::BASE, $archive)->assertOk()->assertJsonPath('data.created', false);
        $this->upload(self::BASE . '/preview', $archive)->assertOk()->assertJsonPath('data.existing', true);

        $response = $this->getJson(self::BASE)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.counts.answer_rows', 3);
        $this->assertArrayNotHasKey('staff', $response->json('data.0'));
        $this->assertArrayNotHasKey('sessions', $response->json('data.0'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('Synthetic confidential answer', $response->getContent());
    }

    public function test_admin_can_view_session_details_while_lists_exclude_answer_text(): void
    {
        $this->signIn('admin');
        $archive = $this->fixture();
        $this->upload(self::BASE, $archive)->assertCreated();

        $summary = $this->getJson(self::BASE . '/' . self::ID)->assertOk()
            ->assertJsonPath('data.staff.0.legacy_staff_id', 1)
            ->assertJsonPath('data.sessions.0.answer_count', 2);
        $this->assertArrayNotHasKey('answers', $summary->json('data.sessions.0'));
        $this->assertStringNotContainsString('Synthetic confidential answer', $summary->getContent());
        $this->assertStringContainsString('no-store', $summary->headers->get('Cache-Control'));

        $session = $this->getJson(self::BASE . '/' . self::ID . '/sessions/10')->assertOk()
            ->assertJsonPath('data.legacy_session_id', 10)
            ->assertJsonCount(2, 'data.answers')
            ->assertJsonPath('data.answers.0.text', 'Synthetic confidential answer one')
            ->assertJsonPath('data.answers.1.text', 'Synthetic confidential answer revision')
            ->assertJsonPath('data.answers.0.duplicate_question_answer', true)
            ->assertJsonPath('data.answers.0.question_snapshot.text', 'Original synthetic question');
        $this->assertStringContainsString('no-store', $session->headers->get('Cache-Control'));
        $this->getJson(self::BASE . '/' . self::ID . '/sessions/999')->assertNotFound();
        $this->getJson(self::BASE . '/' . str_repeat('b', 64))->assertNotFound();
        $this->getJson(self::BASE . '/invalid-id')->assertNotFound();
        $this->getJson(self::BASE . '/%2e%2e%2fprivate')->assertNotFound();
    }

    public function test_conflicting_source_cannot_overwrite_existing_archive_even_during_preview(): void
    {
        $this->signIn('admin');
        $archive = $this->fixture();
        $this->upload(self::BASE, $archive)->assertCreated();
        $stored = Storage::disk('local')->get('supervision-archives/' . self::ID . '.json');
        $archive['sessions'][0]['answers'][0]['text'] = 'Altered answer';
        $this->upload(self::BASE . '/preview', $archive)->assertStatus(409);
        $this->upload(self::BASE, $archive)->assertStatus(409);
        $this->assertSame($stored, Storage::disk('local')->get('supervision-archives/' . self::ID . '.json'));
    }

    public function test_import_rejects_pending_sessions_wrong_counts_orphan_links_and_duplicate_ids(): void
    {
        $this->signIn('admin');
        $mutations = [
            'pending session' => function (&$data) { $data['sessions'][0]['status'] = 'active'; },
            'no saved answers' => function (&$data) { $data['sessions'][0]['answers'] = []; },
            'blank responses only' => function (&$data) { foreach ($data['sessions'][0]['answers'] as &$answer) { $answer['text'] = ' '; } },
            'wrong count' => function (&$data) { $data['counts']['answer_rows'] = 999; },
            'orphan staff' => function (&$data) { $data['sessions'][0]['legacy_staff_id'] = 99; },
            'orphan question' => function (&$data) { $data['sessions'][0]['answers'][0]['legacy_question_id'] = 99; },
            'duplicate answer ID' => function (&$data) { $data['sessions'][1]['answers'][0]['legacy_answer_id'] = 11; },
            'duplicate session ID' => function (&$data) { $data['sessions'][1]['legacy_session_id'] = 10; },
            'staff session mismatch' => function (&$data) { $data['staff'][0]['session_ids'] = [10]; },
            'rewritten question snapshot' => function (&$data) { $data['sessions'][0]['answers'][0]['question_snapshot']['text'] = 'Different prompt'; },
            'unreferenced staff' => function (&$data) { $data['staff'][] = ['legacy_staff_id' => 2, 'fullname' => 'Unused synthetic staff', 'session_ids' => [999]]; },
        ];
        foreach ($mutations as $mutate) {
            $data = $this->fixture();
            $mutate($data);
            $this->upload(self::BASE, $data)->assertUnprocessable();
        }
        $this->post(self::BASE, ['archive' => UploadedFile::fake()->createWithContent('archive.json', 'invalid json')], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->post(self::BASE, ['archive' => UploadedFile::fake()->create('archive.json', 5121)], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unknown_fields_are_not_stored_and_the_cli_supports_dry_run(): void
    {
        $this->signIn('admin');
        $archive = $this->fixture();
        $archive['staff'][0]['email'] = 'excluded@example.test';
        $archive['users'] = [['password' => 'excluded synthetic value']];
        $this->upload(self::BASE, $archive)->assertCreated();
        $stored = Storage::disk('local')->get('supervision-archives/' . self::ID . '.json');
        $this->assertStringNotContainsString('excluded@example.test', $stored);
        $this->assertStringNotContainsString('excluded synthetic value', $stored);

        $path = tempnam(sys_get_temp_dir(), 'hpw-archive-test-');
        try {
            file_put_contents($path, json_encode($this->fixture()));
            $this->artisan('supervision:archive-import', ['file' => $path, '--dry-run' => true])->assertExitCode(0);
        } finally {
            unlink($path);
        }
    }

    private function signIn(string $role): void
    {
        $user = new User(['name' => 'Synthetic user', 'role' => $role, 'is_active' => true]);
        $user->id = 987;
        Sanctum::actingAs($user);
    }

    private function upload(string $uri, array $archive)
    {
        return $this->post($uri, ['archive' => UploadedFile::fake()->createWithContent('archive.json', json_encode($archive))], ['Accept' => 'application/json']);
    }

    private function fixture(): array
    {
        $snapshot = ['text' => 'Original synthetic question', 'question_type' => null, 'options' => ['a' => null, 'b' => null, 'd' => null, 'e' => null], 'created_at' => null, 'updated_at' => null];
        $answer = function ($id, $text) use ($snapshot) {
            return ['legacy_answer_id' => $id, 'legacy_question_id' => 1, 'text' => $text, 'question_snapshot' => $snapshot, 'created_at' => '2023-12-05 12:00:00', 'updated_at' => '2023-12-05 12:00:00', 'status' => 'active'];
        };
        return [
            'schema_version' => 1,
            'source' => ['sha256' => self::ID, 'file_name' => 'synthetic.sql', 'backup_database' => 'synthetic', 'exported_at_label' => 'Example backup'],
            'counts' => ['staff' => 1, 'completed_sessions' => 2, 'answer_rows' => 3, 'questions' => 1, 'duplicate_answer_groups' => 1, 'extra_duplicate_rows' => 1, 'sessions_with_question_snapshot_uncertainty' => 0, 'answers_with_question_snapshot_uncertainty' => 0],
            'limitations' => ['Scheduled dates are not verified completion dates.'],
            'staff' => [['legacy_staff_id' => 1, 'fullname' => 'Synthetic Staff', 'staff_id' => 'QA-1', 'session_ids' => [10, 11]]],
            'questions' => [['legacy_question_id' => 1] + $snapshot],
            'sessions' => [
                ['legacy_session_id' => 10, 'legacy_staff_id' => 1, 'status' => 'completed', 'scheduled_date' => '2023-12-01', 'answers' => [$answer(11, 'Synthetic confidential answer one'), $answer(12, 'Synthetic confidential answer revision')]],
                ['legacy_session_id' => 11, 'legacy_staff_id' => 1, 'status' => 'completed', 'scheduled_date' => '2023-12-02', 'answers' => [$answer(13, 'Synthetic confidential answer two')]],
            ],
        ];
    }
}
