<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffCourseExpertiseMigrationTest extends TestCase
{
    private $testPrefix;
    private $connectionName = 'expertise_migration_test';

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('RUN_MYSQL_MIGRATION_TESTS') !== '1') {
            $this->markTestSkipped('Set RUN_MYSQL_MIGRATION_TESTS=1 to run isolated local MySQL migration checks.');
        }

        $configuration = config('database.connections.mysql');
        if (!in_array($configuration['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->markTestSkipped('The migration regression checks run only against a local MySQL host.');
        }

        // A short random prefix isolates every test table and keeps generated FK names below 64 characters.
        $this->testPrefix = 'hpwm_' . bin2hex(random_bytes(3)) . '_';
        $configuration['url'] = null;
        $configuration['prefix'] = $this->testPrefix;
        $configuration['prefix_indexes'] = true;
        config([
            'database.default' => $this->connectionName,
            'database.connections.' . $this->connectionName => $configuration,
        ]);

        require_once database_path('migrations/2026_11_16_120000_create_staff_course_expertise_table.php');

        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
        });
        Schema::create('staff_records', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('fullname');
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->id();
            $table->string('title');
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Synthetic administrator']);
        DB::table('staff_records')->insert(['id' => 1, 'fullname' => 'Synthetic staff']);
        DB::table('courses')->insert(['id' => 1, 'title' => 'Synthetic course']);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->testPrefix && preg_match('/^hpwm_[a-f0-9]{6}_$/', $this->testPrefix)) {
                // These four exact names resolve only to this test's randomly prefixed tables.
                $schema = DB::connection($this->connectionName)->getSchemaBuilder();
                foreach (['staff_course_expertise', 'courses', 'staff_records', 'users'] as $table) {
                    $schema->dropIfExists($table);
                }
                DB::purge($this->connectionName);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_new_install_converts_legacy_course_engine_without_losing_data(): void
    {
        $this->assertSame('MyISAM', $this->tableEngine('courses'));

        $migration = new \CreateStaffCourseExpertiseTable();
        $migration->up();

        $this->assertSame('InnoDB', $this->tableEngine('courses'));
        $this->assertSame('InnoDB', $this->tableEngine('staff_course_expertise'));
        $this->assertSame('Synthetic course', DB::table('courses')->where('id', 1)->value('title'));
        $this->assertCompleteConstraints();
    }

    public function test_retry_repairs_partial_table_and_preserves_existing_ratings(): void
    {
        // MySQL auto-commits CREATE TABLE and the first FK before the original course FK fails.
        Schema::create('staff_course_expertise', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('staff_record_id')->constrained('staff_records')->onDelete('cascade');
            $table->unsignedBigInteger('course_id');
            $table->string('expertise', 20);
            $table->unsignedBigInteger('rated_by')->nullable();
            $table->timestamps();
        });
        DB::table('staff_course_expertise')->insert([
            'staff_record_id' => 1,
            'course_id' => 1,
            'expertise' => 'advanced',
            'rated_by' => 1,
            'created_at' => '2026-11-16 12:00:00',
            'updated_at' => '2026-11-16 12:00:00',
        ]);

        $migration = new \CreateStaffCourseExpertiseTable();
        $migration->up();
        $migration->up();

        $this->assertCompleteConstraints();
        $this->assertSame('InnoDB', $this->tableEngine('courses'));
        $this->assertSame(1, DB::table('staff_course_expertise')->count());
        $this->assertSame('advanced', DB::table('staff_course_expertise')->where('staff_record_id', 1)->value('expertise'));
        $this->assertSame('Synthetic course', DB::table('courses')->where('id', 1)->value('title'));

        // Verify the repaired DELETE action is enforced by MySQL, not just recorded as metadata.
        DB::table('courses')->where('id', 1)->delete();
        $this->assertSame(0, DB::table('staff_course_expertise')->count());
    }

    private function tableEngine(string $table): string
    {
        $row = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$this->testPrefix . $table]
        );

        return $row->engine;
    }

    private function assertCompleteConstraints(): void
    {
        $foreignKeys = DB::select(
            'SELECT COLUMN_NAME AS column_name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$this->testPrefix . 'staff_course_expertise']
        );
        $columns = array_map(function ($key) { return $key->column_name; }, $foreignKeys);
        sort($columns);
        $this->assertSame(['course_id', 'rated_by', 'staff_record_id'], $columns);

        $indexes = DB::select(
            'SELECT INDEX_NAME AS index_name, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND NON_UNIQUE = 0 GROUP BY INDEX_NAME',
            [$this->testPrefix . 'staff_course_expertise']
        );
        $uniqueColumns = array_map(function ($index) { return $index->indexed_columns; }, $indexes);
        $this->assertContains('staff_record_id,course_id', $uniqueColumns);
    }
}
