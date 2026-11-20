<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            // Monday of week 1; week N runs from starts_on + (N-1) weeks.
            $table->date('starts_on');
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();
        });

        // A student's FYP for one semester.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('stage', 10)->default('FYP1');
            $table->timestamps();

            $table->unique(['student_id', 'semester_id']);
        });

        Schema::table('logbooks', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('student_id')->constrained()->restrictOnDelete();
        });

        // Entries written before semesters existed go into one current semester.
        if ($first = DB::table('logbooks')->min('entry_date')) {
            $start = Carbon::parse($first)->startOfWeek();
            $semesterId = DB::table('semesters')->insertGetId([
                'name' => 'Semester 1 '.$start->year.'/'.($start->year + 1),
                'starts_on' => $start->toDateString(),
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('logbooks')->update(['semester_id' => $semesterId]);
        }

        // One entry per week per semester (was: per week, ever).
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'week_no']);
            $table->unique(['student_id', 'semester_id', 'week_no']);
        });
    }

    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'semester_id', 'week_no']);
            $table->dropConstrainedForeignId('semester_id');
            $table->unique(['student_id', 'week_no']);
        });

        Schema::dropIfExists('projects');
        Schema::dropIfExists('semesters');
    }
};
