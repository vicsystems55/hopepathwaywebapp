<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStaffCourseExpertiseTable extends Migration
{
    public function up()
    {
        $isMySql = DB::connection()->getDriverName() === 'mysql';

        if ($isMySql) {
            // The deployed database originally used MyISAM for courses. All
            // referenced tables must support foreign keys before adding them.
            foreach (['staff_records', 'courses', 'users'] as $parent) {
                $this->ensureInnoDb($parent);
            }
        }

        if (!Schema::hasTable('staff_course_expertise')) {
            Schema::create('staff_course_expertise', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('staff_record_id')->constrained('staff_records')->onDelete('cascade');
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->string('expertise', 20);
                $table->foreignId('rated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->unique(['staff_record_id', 'course_id'], 'staff_course_expertise_unique');
            });
        }

        if ($isMySql) {
            // MySQL DDL is not transactional. A failed Schema::create can leave
            // the table and only some constraints behind. Complete it in place
            // on retry, retaining any existing expertise ratings.
            $this->ensureInnoDb('staff_course_expertise');
            $this->ensureForeignKey('staff_record_id', 'staff_records', 'cascade');
            $this->ensureForeignKey('course_id', 'courses', 'cascade');
            $this->ensureForeignKey('rated_by', 'users', 'set null');

            $unique = DB::selectOne(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? AND NON_UNIQUE = 0 LIMIT 1',
                [DB::connection()->getDatabaseName(), $this->physicalTable('staff_course_expertise'), 'staff_course_expertise_unique']
            );

            if (!$unique) {
                Schema::table('staff_course_expertise', function (Blueprint $table) {
                    $table->unique(['staff_record_id', 'course_id'], 'staff_course_expertise_unique');
                });
            }
        }
    }

    private function ensureInnoDb(string $table): void
    {
        $metadata = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::connection()->getDatabaseName(), $this->physicalTable($table)]
        );

        if (!$metadata) {
            throw new \RuntimeException('Required table does not exist: ' . $table);
        }

        if (strcasecmp($metadata->engine, 'InnoDB') !== 0) {
            $wrapped = DB::connection()->getQueryGrammar()->wrapTable($table);
            DB::statement('ALTER TABLE ' . $wrapped . ' ENGINE=InnoDB');
        }
    }

    private function ensureForeignKey(string $column, string $parent, string $onDelete): void
    {
        $foreign = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ? LIMIT 1',
            [DB::connection()->getDatabaseName(), $this->physicalTable('staff_course_expertise'), $column, $this->physicalTable($parent), 'id']
        );

        if (!$foreign) {
            Schema::table('staff_course_expertise', function (Blueprint $table) use ($column, $parent, $onDelete) {
                $table->foreign($column)->references('id')->on($parent)->onDelete($onDelete);
            });
        }
    }

    private function physicalTable(string $table): string
    {
        return DB::connection()->getTablePrefix() . $table;
    }

    public function down()
    {
        Schema::dropIfExists('staff_course_expertise');
    }
}
