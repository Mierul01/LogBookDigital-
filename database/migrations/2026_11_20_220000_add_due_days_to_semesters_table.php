<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            // A week's entry is due this many days after the week ends (Sunday). 3 = Wednesday night.
            $table->unsignedTinyInteger('due_days')->default(3)->after('starts_on');
        });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn('due_days');
        });
    }
};
