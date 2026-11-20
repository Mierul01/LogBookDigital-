<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            // face_to_face, online or none (no meeting with the supervisor that week).
            $table->string('meeting_mode', 20)->default('face_to_face')->after('entry_date');
            $table->time('meeting_time')->nullable()->after('meeting_mode');
            $table->unsignedSmallInteger('meeting_minutes')->nullable()->after('meeting_time');
            $table->string('meeting_place')->nullable()->after('meeting_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropColumn(['meeting_mode', 'meeting_time', 'meeting_minutes', 'meeting_place']);
        });
    }
};
