<?php

namespace Tests\Feature;

use App\Models\StaffRecord;
use App\Models\StaffSupervisionSchedule;
use App\Models\SupervisionAnswer;
use App\Models\SupervisionQuestion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupervisionDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use synthetic records on an isolated connection, never the local staff database.
        config([
            'database.default' => 'supervision_details_test',
            'database.connections.supervision_details_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
        });
        Schema::create('training_programmes', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('staff_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fullname');
            $table->string('staff_id');
            $table->timestamps();
        });
        Schema::create('staff_supervision_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_record_id');
            $table->string('next_supervision_date');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('supervision_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('question_type')->nullable();
            $table->timestamps();
        });
        Schema::create('supervision_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_supervision_schedule_id');
            $table->unsignedBigInteger('supervision_question_id');
            $table->text('answer')->nullable();
            $table->timestamps();
        });
        foreach (['staff_qualifications', 'staff_documents', 'staff_trainings'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_record_id');
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::disconnect('supervision_details_test');
        parent::tearDown();
    }

    public function test_supervision_details_require_authentication_and_management_permission(): void
    {
        $this->getJson('/api/staff-supervision/1')->assertUnauthorized();
        $this->signIn('staff');
        $this->getJson('/api/staff-supervision/1')->assertForbidden();
        $this->signIn('admin');
        config(['access.roles.admin' => []]);
        $this->getJson('/api/staff-supervision/1')->assertForbidden();
    }

    public function test_missing_schedule_returns_not_found(): void
    {
        $this->signIn('admin');
        $this->getJson('/api/staff-supervision/999')->assertNotFound();
    }

    public function test_details_preserve_all_answer_versions_and_missing_question_references_without_writes(): void
    {
        $this->signIn('admin');
        $staff = $this->staff();
        $schedule = $this->schedule($staff, '2026-09-01', 'completed');
        $other = $this->schedule($staff, '2026-10-01');
        SupervisionQuestion::create(['id' => 2, 'question' => 'Synthetic second question']);
        SupervisionQuestion::create(['id' => 1, 'question' => 'Synthetic first question']);
        $this->answer(31, $schedule, 2, '0');
        $this->answer(25, $schedule, 1, 'Synthetic revised response');
        $this->answer(12, $schedule, 1, 'Synthetic original response');
        $this->answer(40, $schedule, 99, 'Synthetic response with a deleted question');
        $this->answer(44, $schedule, 99, '');
        $this->answer(50, $other, 1, 'A different session must not be included');
        $before = SupervisionAnswer::orderBy('id')->get()->toArray();

        $response = $this->getJson('/api/staff-supervision/' . $schedule->id)->assertOk()
            ->assertJsonPath('scheduleData.id', $schedule->id)
            ->assertJsonPath('scheduleData.staff.id', $staff->id)
            ->assertJsonPath('supervisionQuestions.0.id', 1)
            ->assertJsonPath('supervisionQuestions.1.id', 2)
            ->assertJsonCount(5, 'supervisionAnswers')
            ->assertJsonPath('supervisionAnswers.0.id', 12)
            ->assertJsonPath('supervisionAnswers.1.id', 25)
            ->assertJsonPath('supervisionAnswers.2.answer', '0')
            ->assertJsonPath('supervisionAnswers.0.questions.question', 'Synthetic first question')
            ->assertJsonPath('supervisionAnswers.3.supervision_question_id', 99)
            ->assertJsonPath('supervisionAnswers.3.questions', null)
            ->assertJsonPath('supervisionAnswers.4.answer', '');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertSame($before, SupervisionAnswer::orderBy('id')->get()->toArray());
        $this->assertSame('completed', $schedule->fresh()->status);
    }

    public function test_staff_profile_exposes_counts_without_answer_text_and_orders_equal_dates_consistently(): void
    {
        $this->signIn('admin');
        $staff = $this->staff();
        $earlier = $this->schedule($staff, '2026-08-01');
        $first = $this->schedule($staff, '2026-09-01', 'completed');
        $second = $this->schedule($staff, '2026-09-01', 'completed');
        $this->answer(1, $first, 1, 'Synthetic private original response');
        $this->answer(2, $first, 1, 'Synthetic private revised response');
        $this->answer(3, $second, 1, 'Synthetic private response');
        DB::enableQueryLog();

        $response = $this->getJson('/api/staff-records/' . $staff->id)->assertOk()
            ->assertJsonCount(3, 'data.supervision_schedule')
            ->assertJsonPath('data.supervision_schedule.0.id', $second->id)
            ->assertJsonPath('data.supervision_schedule.0.answers_count', 1)
            ->assertJsonPath('data.supervision_schedule.1.id', $first->id)
            ->assertJsonPath('data.supervision_schedule.1.answers_count', 2)
            ->assertJsonPath('data.supervision_schedule.2.id', $earlier->id)
            ->assertJsonPath('data.supervision_schedule.2.answers_count', 0);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        foreach ($response->json('data.supervision_schedule') as $schedule) {
            $this->assertArrayNotHasKey('answers', $schedule);
        }
        $this->assertStringNotContainsString('Synthetic private', $response->getContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        // All counts come from the single schedule eager-load query, not one query per session.
        $answerQueries = array_filter($queries, function ($query) {
            return strpos($query['query'], 'supervision_answers') !== false;
        });
        $this->assertCount(1, $answerQueries);
        $this->assertStringContainsString('count(*)', reset($answerQueries)['query']);
    }

    private function signIn(string $role): void
    {
        $user = new User(['name' => 'Synthetic user', 'email' => 'user@example.test', 'role' => $role, 'is_active' => true]);
        $user->id = 100;
        Sanctum::actingAs($user);
    }

    private function staff(): StaffRecord
    {
        return StaffRecord::create(['fullname' => 'Synthetic staff', 'staff_id' => 'TEST-001']);
    }

    private function schedule(StaffRecord $staff, string $date, string $status = 'active'): StaffSupervisionSchedule
    {
        return StaffSupervisionSchedule::create(['staff_record_id' => $staff->id, 'next_supervision_date' => $date, 'status' => $status]);
    }

    private function answer(int $id, StaffSupervisionSchedule $schedule, int $questionId, string $text): void
    {
        SupervisionAnswer::create(['id' => $id, 'staff_supervision_schedule_id' => $schedule->id, 'supervision_question_id' => $questionId, 'answer' => $text]);
    }
}
