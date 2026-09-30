<?php

namespace Database\Seeders;

use App\Models\Logbook;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo accounts for local development.
     *
     * Supervisor: dr.aisyah / password
     * Students:   A192910, A192911 / password
     */
    public function run(): void
    {
        $supervisor = User::factory()->supervisor()->create([
            'username' => 'dr.aisyah',
            'name' => 'Dr. Aisyah Rahman',
            'email' => 'aisyah@ukm.edu.my',
        ]);

        $amirul = User::factory()->supervisedBy($supervisor)->create([
            'username' => 'A192910',
            'name' => 'Muhamad Amirul Aiman',
            'email' => 'a192910@siswa.ukm.edu.my',
        ]);

        $nurul = User::factory()->supervisedBy($supervisor)->create([
            'username' => 'A192911',
            'name' => 'Nurul Huda Ismail',
            'email' => null,
        ]);

        $weeks = [
            ['Set up the project repository and drafted the system requirements.', 'Requirements gathering in progress.', 'Unclear scope for the reporting module.', 'Finalise use-case diagram.'],
            ['Reviewed the use-case diagram with supervisor; agreed on two user roles.', 'Use-case diagram approved.', 'None.', 'Design the database schema (ERD).'],
            ['Presented the ERD. Supervisor suggested merging the student and supervisor tables.', 'ERD revised.', 'Unsure how to handle signatures.', 'Build login and role-based access.'],
        ];

        foreach ($weeks as $i => [$progress, $status, $problem, $task]) {
            $factory = $i < 2 ? Logbook::factory()->reviewed() : Logbook::factory();

            $factory->for($amirul, 'student')->create([
                'week_no' => $i + 1,
                'entry_date' => now()->subWeeks(count($weeks) - $i)->startOfWeek(),
                'progress' => $progress,
                'current_status' => $status,
                'problem' => $problem,
                'next_week_task' => $task,
                'supervisor_comment' => $i < 2 ? 'Good progress. Keep it up.' : null,
            ]);
        }

        Logbook::factory()->for($nurul, 'student')->create([
            'week_no' => 1,
            'entry_date' => now()->subWeek()->startOfWeek(),
            'progress' => 'Discussed the project title and scope. Agreed to build a mobile attendance app using QR codes.',
            'current_status' => 'Project proposal drafted.',
            'problem' => 'Not sure which framework to use for the mobile app.',
            'next_week_task' => 'Compare Flutter and React Native, then write the literature review outline.',
        ]);
    }
}
