<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePerformance;
use App\Models\StaffRecord;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformanceMatrixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A dedicated in-memory connection prevents these write tests touching local staff data.
        config([
            'database.default' => 'matrix_test',
            'database.connections.matrix_test' => [
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
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('staff_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fullname');
            $table->string('email');
            $table->string('staff_id');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('level')->default('beginner');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('course_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->integer('total_score');
            $table->integer('expected_score');
            $table->string('grade')->nullable();
            $table->string('status')->default('passed');
            $table->timestamps();
        });

        require_once database_path('migrations/2026_11_16_120000_create_staff_course_expertise_table.php');
        (new \CreateStaffCourseExpertiseTable())->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('matrix_test');
        parent::tearDown();
    }

    public function test_admin_can_rate_unlinked_staff_and_change_or_clear_that_rating(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);
        $staff = $this->staff();
        $course = Course::create(['title' => 'Safeguarding']);
        $uri = $this->cellUri($staff, $course);

        $this->putJson($uri, ['expertise' => 'advanced'])->assertOk()
            ->assertJsonPath('data.result.expertise', 'advanced')
            ->assertJsonPath('data.result.source', 'manual')
            ->assertJsonPath('data.result.percentage', null)
            ->assertJsonPath('data.result.rated_by_name', $admin->name);

        $this->getJson('/api/performance-matrix')->assertOk()
            ->assertJsonPath('data.staff.0.has_login', false)
            ->assertJsonPath('data.staff.0.results.' . $course->id . '.expertise', 'advanced');

        $this->putJson($uri, ['expertise' => 'beginner'])->assertOk();
        $this->assertDatabaseCount('staff_course_expertise', 1);
        $this->putJson($uri, ['expertise' => 'unassessed'])->assertOk()
            ->assertJsonPath('data.result.expertise', 'unassessed');
        $this->getJson('/api/performance-matrix')->assertOk()
            ->assertJsonPath('data.staff.0.results.' . $course->id . '.expertise', 'unassessed');

        $this->deleteJson($uri)->assertOk()->assertJsonPath('data.result', null);
        $this->assertDatabaseCount('staff_course_expertise', 0);
        $this->assertDatabaseCount('course_performances', 0);
    }

    public function test_manual_rating_overrides_quiz_result_and_reset_restores_best_score(): void
    {
        Sanctum::actingAs($this->user('admin'));
        $account = $this->user('staff');
        $staff = $this->staff($account->id);
        $course = Course::create(['title' => 'Medication']);
        $this->performance($account, $course, 40, 100);
        $best = $this->performance($account, $course, 85, 100);
        $this->performance($account, $course, 60, 100);

        $this->putJson($this->cellUri($staff, $course), ['expertise' => 'average'])->assertOk()
            ->assertJsonPath('data.result.percentage', null)
            ->assertJsonPath('data.result.assessment_percentage', 85);

        $this->deleteJson($this->cellUri($staff, $course))->assertOk()
            ->assertJsonPath('data.result.performance_id', $best->id)
            ->assertJsonPath('data.result.expertise', 'advanced')
            ->assertJsonPath('data.result.percentage', 85)
            ->assertJsonPath('data.result.source', 'assessment')
            ->assertJsonPath('data.result.attempts', 3);
        $this->assertDatabaseCount('course_performances', 3);
    }

    public function test_course_codes_are_sequential_and_invalid_scores_do_not_create_results(): void
    {
        Sanctum::actingAs($this->user('admin'));
        $account = $this->user('staff');
        $this->staff($account->id);
        $course = Course::create(['title' => 'Z course']);
        $second = Course::create(['title' => 'A course']);
        $this->performance($account, $course, 496, 1000);
        $this->performance($account, $second, 100, 0);

        $response = $this->getJson('/api/performance-matrix')->assertOk()
            ->assertJsonPath('data.courses.0.id', $course->id)
            ->assertJsonPath('data.courses.0.code', '01')
            ->assertJsonPath('data.courses.1.code', '02')
            ->assertJsonPath('data.staff.0.results.' . $course->id . '.expertise', 'beginner')
            ->assertJsonPath('data.staff.0.results.' . $course->id . '.percentage', 49.6);
        $this->assertArrayNotHasKey($second->id, $response->json('data.staff.0.results'));
    }

    public function test_invalid_levels_are_rejected_and_non_admins_cannot_read_or_change_ratings(): void
    {
        $staff = $this->staff();
        $course = Course::create(['title' => 'First aid']);
        Sanctum::actingAs($this->user('admin'));
        $this->putJson($this->cellUri($staff, $course), ['expertise' => 'expert'])->assertUnprocessable()
            ->assertJsonValidationErrors('expertise');
        $this->assertDatabaseCount('staff_course_expertise', 0);

        // Even an accidentally expanded permission cannot bypass the explicit administrator role.
        config(['access.roles.staff' => ['performance.view-all']]);
        Sanctum::actingAs($this->user('staff'));
        $this->getJson('/api/performance-matrix')->assertForbidden();
        $this->putJson($this->cellUri($staff, $course), ['expertise' => 'advanced'])->assertForbidden();
        $this->deleteJson($this->cellUri($staff, $course))->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create(['name' => ucfirst($role), 'email' => uniqid() . '@example.test', 'role' => $role, 'is_active' => true]);
    }

    private function staff(?int $userId = null): StaffRecord
    {
        return StaffRecord::create(['user_id' => $userId, 'fullname' => 'Care Worker', 'email' => 'staff@example.test', 'staff_id' => 'HPW-001']);
    }

    private function performance(User $user, Course $course, int $score, int $expected): CoursePerformance
    {
        return CoursePerformance::create(['user_id' => $user->id, 'course_id' => $course->id, 'total_score' => $score, 'expected_score' => $expected]);
    }

    private function cellUri(StaffRecord $staff, Course $course): string
    {
        return '/api/performance-matrix/staff/' . $staff->id . '/courses/' . $course->id;
    }
}
