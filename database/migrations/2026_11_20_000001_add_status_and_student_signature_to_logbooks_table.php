<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            // pending = waiting for the supervisor, revision = sent back to the student, approved = signed.
            $table->string('status', 20)->default('pending')->after('next_week_task')->index();
            $table->longText('student_signature')->nullable()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('student_signature');
        });

        DB::table('logbooks')->whereNotNull('reviewed_at')->update(['status' => 'approved']);
        DB::table('logbooks')->update(['submitted_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'student_signature', 'submitted_at']);
        });
    }
};
