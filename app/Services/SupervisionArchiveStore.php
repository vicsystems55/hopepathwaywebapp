<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupervisionArchiveStore
{
    public const DIRECTORY = 'supervision-archives';
    public const MAX_BYTES = 5242880;

    public function validateJson(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            $this->invalid('The archive must not exceed 5 MB.');
        }

        try {
            $data = json_decode($json, true, 40, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->invalid('The archive must contain valid JSON.');
        }
        if (!is_array($data)) {
            $this->invalid('The archive must contain a JSON object.');
        }

        Validator::make($data, [
            'schema_version' => 'required|integer|in:1',
            'source' => 'required|array',
            'source.file_name' => 'required|string|max:255',
            'source.sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'source.backup_database' => 'nullable|string|max:255',
            'source.exported_at_label' => 'nullable|string|max:255',
            'counts' => 'required|array',
            'limitations' => 'required|array|max:30',
            'limitations.*' => 'required|string|max:3000',
            'staff' => 'required|array|min:1|max:1000',
            'staff.*.legacy_staff_id' => 'required|integer|min:1|distinct',
            'staff.*.fullname' => 'required|string|max:255',
            'staff.*.staff_id' => 'nullable|string|max:255',
            'staff.*.session_ids' => 'required|array|min:1|max:5000',
            'staff.*.session_ids.*' => 'required|integer|min:1',
            'questions' => 'required|array|min:1|max:1000',
            'questions.*.legacy_question_id' => 'required|integer|min:1|distinct',
            'questions.*.text' => 'required|string|max:100000',
            'questions.*.question_type' => 'nullable|string|max:255',
            'questions.*.options' => 'required|array',
            'questions.*.options.*' => 'nullable|string|max:100000',
            'questions.*.created_at' => 'nullable|string|max:64',
            'questions.*.updated_at' => 'nullable|string|max:64',
            'sessions' => 'required|array|min:1|max:5000',
            'sessions.*.legacy_session_id' => 'required|integer|min:1|distinct',
            'sessions.*.legacy_staff_id' => 'required|integer|min:1',
            'sessions.*.status' => 'required|in:completed',
            'sessions.*.scheduled_date' => 'nullable|string|max:255',
            'sessions.*.created_at' => 'nullable|string|max:64',
            'sessions.*.updated_at' => 'nullable|string|max:64',
            'sessions.*.answers' => 'required|array|min:1|max:1000',
            'sessions.*.answers.*.legacy_answer_id' => 'required|integer|min:1',
            'sessions.*.answers.*.legacy_question_id' => 'required|integer|min:1',
            'sessions.*.answers.*.text' => 'present|string|max:500000',
            'sessions.*.answers.*.question_snapshot' => 'required|array',
            'sessions.*.answers.*.question_snapshot.text' => 'required|string|max:100000',
            'sessions.*.answers.*.question_snapshot.question_type' => 'nullable|string|max:255',
            'sessions.*.answers.*.question_snapshot.options' => 'required|array',
            'sessions.*.answers.*.question_snapshot.options.*' => 'nullable|string|max:100000',
            'sessions.*.answers.*.question_snapshot.created_at' => 'nullable|string|max:64',
            'sessions.*.answers.*.question_snapshot.updated_at' => 'nullable|string|max:64',
            'sessions.*.answers.*.created_at' => 'nullable|string|max:64',
            'sessions.*.answers.*.updated_at' => 'nullable|string|max:64',
            'sessions.*.answers.*.status' => 'nullable|string|max:64',
        ])->validate();

        $questions = [];
        foreach ($data['questions'] as $question) {
            $id = (int) $question['legacy_question_id'];
            $questions[$id] = ['legacy_question_id' => $id] + $this->questionSnapshot($question);
        }
        $staff = [];
        foreach ($data['staff'] as $person) {
            $id = (int) $person['legacy_staff_id'];
            $staff[$id] = [
                'legacy_staff_id' => $id,
                'fullname' => $person['fullname'],
                'staff_id' => $person['staff_id'] ?? null,
                'session_ids' => [],
            ];
        }

        $sessions = [];
        $seenAnswers = [];
        $usedQuestions = [];
        $counts = array_fill_keys([
            'staff', 'completed_sessions', 'answer_rows', 'questions', 'duplicate_answer_groups',
            'extra_duplicate_rows', 'sessions_with_question_snapshot_uncertainty', 'answers_with_question_snapshot_uncertainty',
        ], 0);

        foreach ($data['sessions'] as $session) {
            $staffId = (int) $session['legacy_staff_id'];
            if (!isset($staff[$staffId])) {
                $this->invalid('Every completed session must reference staff included in the archive.');
            }
            if (!collect($session['answers'])->contains(function ($answer) { return trim($answer['text']) !== ''; })) {
                $this->invalid('Every completed session must include a saved response.');
            }

            $answers = [];
            $groups = [];
            foreach ($session['answers'] as $answer) {
                $answerId = (int) $answer['legacy_answer_id'];
                $questionId = (int) $answer['legacy_question_id'];
                if (isset($seenAnswers[$answerId])) {
                    $this->invalid('Legacy answer IDs must be unique across the archive.');
                }
                if (!isset($questions[$questionId])) {
                    $this->invalid('Every saved answer must reference a question included in the archive.');
                }
                $question = $questions[$questionId];
                unset($question['legacy_question_id']);
                if ($this->questionSnapshot($answer['question_snapshot']) !== $question) {
                    $this->invalid('Answer question snapshots must match the archive question bank.');
                }
                $seenAnswers[$answerId] = true;
                $usedQuestions[$questionId] = true;
                $groups[$questionId][] = $answerId;
                $questionTime = max($question['created_at'] ?? '', $question['updated_at'] ?? '');
                $uncertain = $questionTime && !empty($answer['created_at']) && $questionTime > $answer['created_at'];
                $answers[] = [
                    'legacy_answer_id' => $answerId,
                    'legacy_question_id' => $questionId,
                    'text' => $answer['text'],
                    'question_snapshot' => $question,
                    'created_at' => $answer['created_at'] ?? null,
                    'updated_at' => $answer['updated_at'] ?? null,
                    'status' => $answer['status'] ?? null,
                    'duplicate_question_answer' => false,
                    'question_snapshot_postdates_answer' => (bool) $uncertain,
                ];
                $counts['answers_with_question_snapshot_uncertainty'] += (int) $uncertain;
            }
            $duplicateGroups = [];
            foreach ($groups as $questionId => $ids) {
                if (count($ids) > 1) {
                    $duplicateGroups[] = ['legacy_question_id' => $questionId, 'answer_ids' => $ids];
                    $counts['extra_duplicate_rows'] += count($ids) - 1;
                }
            }
            foreach ($answers as &$answer) {
                $answer['duplicate_question_answer'] = count($groups[$answer['legacy_question_id']]) > 1;
            }
            unset($answer);

            $createdRange = $this->dateRange(array_column($answers, 'created_at'));
            $updatedRange = $this->dateRange(array_column($answers, 'updated_at'));
            $uncertain = collect($answers)->contains('question_snapshot_postdates_answer', true);
            $sessionId = (int) $session['legacy_session_id'];
            $sessions[] = [
                'legacy_session_id' => $sessionId,
                'legacy_staff_id' => $staffId,
                'status' => 'completed',
                'scheduled_date' => $session['scheduled_date'] ?? null,
                'created_at' => $session['created_at'] ?? null,
                'updated_at' => $session['updated_at'] ?? null,
                'answer_dates' => ['first_saved_at' => $createdRange[0], 'last_saved_at' => $createdRange[1], 'last_updated_at' => $updatedRange[1]],
                'distinct_question_count' => count($groups),
                'duplicate_groups' => $duplicateGroups,
                'question_snapshot_uncertain' => $uncertain,
                'answers' => $answers,
            ];
            $staff[$staffId]['session_ids'][] = $sessionId;
            $counts['answer_rows'] += count($answers);
            $counts['duplicate_answer_groups'] += count($duplicateGroups);
            $counts['sessions_with_question_snapshot_uncertainty'] += (int) $uncertain;
        }

        foreach ($data['staff'] as $person) {
            $expected = $staff[(int) $person['legacy_staff_id']]['session_ids'];
            $declared = array_map('intval', $person['session_ids']);
            sort($expected);
            sort($declared);
            if (!$expected || $expected !== $declared) {
                $this->invalid('Staff session lists must match exactly the completed sessions in the archive.');
            }
        }
        if (count($questions) !== count($usedQuestions)) {
            $this->invalid('Only questions referenced by completed-session answers may be included.');
        }
        $counts['staff'] = count($staff);
        $counts['completed_sessions'] = count($sessions);
        $counts['questions'] = count($questions);
        foreach ($counts as $key => $count) {
            if (!array_key_exists($key, $data['counts']) || $data['counts'][$key] !== $count) {
                $this->invalid('Archive counts do not match its records: ' . $key . '.');
            }
        }

        return [
            'schema_version' => 1,
            'source' => [
                'file_name' => $data['source']['file_name'],
                'sha256' => $data['source']['sha256'],
                'backup_database' => $data['source']['backup_database'] ?? null,
                'exported_at_label' => $data['source']['exported_at_label'] ?? null,
            ],
            'counts' => $counts,
            'limitations' => array_values($data['limitations']),
            'staff' => array_values($staff),
            'questions' => array_values($questions),
            'sessions' => $sessions,
        ];
    }

    public function preview(array $archive): array
    {
        $id = $archive['source']['sha256'];
        $exists = Storage::disk('local')->exists($this->filename($id));
        if ($exists && !hash_equals($this->load($id)['content_sha256'], hash('sha256', $this->encode($archive)))) {
            throw new ConflictHttpException('This backup already has a different archive. Existing records have been preserved.');
        }
        return [
            'id' => $id,
            'source' => $archive['source'],
            'counts' => $archive['counts'],
            'limitations' => $archive['limitations'],
            'staff' => $archive['staff'],
            'existing' => $exists,
        ];
    }

    public function import(array $archive): array
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(self::DIRECTORY);
        $disk->setVisibility(self::DIRECTORY, 'private');
        $lock = fopen($disk->path(self::DIRECTORY . '/.import.lock'), 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('The archive import lock could not be acquired.');
        }
        $temporary = null;
        try {
            $id = $archive['source']['sha256'];
            $fingerprint = hash('sha256', $this->encode($archive));
            $filename = $this->filename($id);
            if ($disk->exists($filename)) {
                $existing = $this->load($id);
                if (!hash_equals($existing['content_sha256'], $fingerprint)) {
                    throw new ConflictHttpException('This backup already has a different archive. Existing records have been preserved.');
                }
                return ['created' => false] + $this->summary($existing);
            }
            $record = ['imported_at' => now()->toIso8601String(), 'content_sha256' => $fingerprint, 'archive' => $archive];
            $temporary = $disk->path(self::DIRECTORY . '/.' . bin2hex(random_bytes(12)) . '.tmp');
            $handle = fopen($temporary, 'xb');
            if (!$handle) {
                throw new \RuntimeException('The private archive file could not be prepared.');
            }
            chmod($temporary, 0600);
            try {
                $contents = $this->encode($record);
                $offset = 0;
                while ($offset < strlen($contents)) {
                    $written = fwrite($handle, substr($contents, $offset));
                    if ($written === false || $written === 0) {
                        throw new \RuntimeException('The private archive file could not be written.');
                    }
                    $offset += $written;
                }
                fflush($handle);
            } finally {
                fclose($handle);
            }
            if (!rename($temporary, $disk->path($filename))) {
                throw new \RuntimeException('The private archive file could not be saved.');
            }
            $temporary = null;
            return ['created' => true] + $this->summary($record);
        } finally {
            if ($temporary && is_file($temporary)) {
                unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function summaries(): array
    {
        $items = [];
        foreach (Storage::disk('local')->files(self::DIRECTORY) as $filename) {
            if (preg_match('/^([a-f0-9]{64})\.json$/D', basename($filename), $match)) {
                $items[] = $this->summary($this->load($match[1]));
            }
        }
        usort($items, function ($left, $right) { return strcmp($right['imported_at'], $left['imported_at']); });
        return $items;
    }

    public function detail(string $id): array
    {
        $record = $this->load($id);
        $archive = $record['archive'];
        $sessions = array_map(function ($session) {
            $session['answer_count'] = count($session['answers']);
            unset($session['answers']);
            return $session;
        }, $archive['sessions']);
        return $this->summary($record) + ['limitations' => $archive['limitations'], 'staff' => $archive['staff'], 'sessions' => $sessions];
    }

    public function session(string $id, int $sessionId): array
    {
        $archive = $this->load($id)['archive'];
        foreach ($archive['sessions'] as $session) {
            if ($session['legacy_session_id'] === $sessionId) {
                return $session;
            }
        }
        throw new NotFoundHttpException('The archived session was not found.');
    }

    private function load(string $id): array
    {
        $filename = $this->filename($id);
        $disk = Storage::disk('local');
        if (!$disk->exists($filename)) {
            throw new NotFoundHttpException('The supervision archive was not found.');
        }
        $record = json_decode($disk->get($filename), true, 50, JSON_THROW_ON_ERROR);
        if (!isset($record['archive'], $record['imported_at'], $record['content_sha256'])
            || $record['archive']['source']['sha256'] !== $id
            || !hash_equals($record['content_sha256'], hash('sha256', $this->encode($record['archive'])))) {
            throw new \RuntimeException('The stored supervision archive could not be verified.');
        }
        return $record;
    }

    private function summary(array $record): array
    {
        return [
            'id' => $record['archive']['source']['sha256'],
            'source' => $record['archive']['source'],
            'counts' => $record['archive']['counts'],
            'imported_at' => $record['imported_at'],
        ];
    }

    private function filename(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $id)) {
            throw new NotFoundHttpException('The supervision archive was not found.');
        }
        return self::DIRECTORY . '/' . $id . '.json';
    }

    private function questionSnapshot(array $question): array
    {
        $options = $question['options'] ?? [];
        return [
            'text' => $question['text'] ?? null,
            'question_type' => $question['question_type'] ?? null,
            'options' => ['a' => $options['a'] ?? null, 'b' => $options['b'] ?? null, 'd' => $options['d'] ?? null, 'e' => $options['e'] ?? null],
            'created_at' => $question['created_at'] ?? null,
            'updated_at' => $question['updated_at'] ?? null,
        ];
    }

    private function dateRange(array $values): array
    {
        $values = array_values(array_filter($values, function ($value) { return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value); }));
        sort($values);
        return [$values[0] ?? null, $values ? end($values) : null];
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['archive' => $message]);
    }
}
