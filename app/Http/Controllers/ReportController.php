<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\User;
use App\Support\ProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Progress reports for supervisors (their students) and the coordinator (the whole faculty).
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$report, $filters] = $this->report($request);

        $students = $report->students($filters['q'], $filters['supervisor'])->paginate(10)->withQueryString();

        return view('reports.index', [
            'report' => $report,
            'filters' => $filters,
            'semesters' => Semester::query()->orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'supervisorOptions' => $request->user()->isCoordinator()
                ? User::query()->where('role', User::ROLE_SUPERVISOR)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'students' => $students,
            'rows' => $report->rows($students->items()),
            'supervisors' => $request->user()->isCoordinator()
                ? $report->supervisors()->paginate(10, ['*'], 'supervisors_page')->withQueryString()
                : null,
        ]);
    }

    /** Student progress as a CSV that opens straight in Excel. */
    public function exportStudents(Request $request): StreamedResponse
    {
        [$report, $filters] = $this->report($request);

        $header = ['Matric no.', 'Name', 'Supervisor', 'Project title', 'Stage', 'Entries', 'Approved', 'Pending',
            'Needs fixing', 'Late', 'Overdue weeks', 'Meetings', 'Last entry'];

        return $this->csv($this->filename('student-progress', $report), $header, function ($write) use ($report, $filters) {
            $report->students($filters['q'], $filters['supervisor'])->chunk(200, function ($students) use ($report, $write) {
                foreach ($report->rows($students) as $r) {
                    $write([$r['matric'], $r['name'], $r['supervisor'], $r['project'], $r['stage'], $r['entries'], $r['approved'],
                        $r['pending'], $r['revision'], $r['late'], $r['overdue'], $r['meetings'], $r['last_entry']?->format('Y-m-d')]);
                }
            });
        });
    }

    /** Totals per supervisor (coordinator only). */
    public function exportSupervisors(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isCoordinator(), 403);
        [$report] = $this->report($request);

        $header = ['Supervisor', 'Username', 'Students', 'Entries', 'Approved', 'Pending', 'Missing weeks'];

        return $this->csv($this->filename('supervisor-summary', $report), $header, function ($write) use ($report) {
            $report->supervisors()->chunk(200, function ($supervisors) use ($report, $write) {
                foreach ($supervisors as $s) {
                    $write([$s->name, $s->username, $s->students_count, $s->entries_count, $s->approved_count, $s->pending_count,
                        max(0, $s->students_count * $report->pastDue() - $s->due_logged)]);
                }
            });
        });
    }

    /** @return array{0: ProgressReport, 1: array<string, mixed>} */
    private function report(Request $request): array
    {
        $semester = Semester::find($request->query('semester')) ?? Semester::current();
        $filters = [
            'semester' => $semester?->id,
            'q' => trim((string) $request->query('q')) ?: null,
            'supervisor' => $request->user()->isCoordinator() && $request->query('supervisor') ? (int) $request->query('supervisor') : null,
        ];

        return [new ProgressReport($request->user(), $semester), $filters];
    }

    private function filename(string $kind, ProgressReport $report): string
    {
        return $kind.'-'.Str::slug($report->semester?->name ?? 'all').'-'.now()->format('Y-m-d').'.csv';
    }

    /** Stream a UTF-8 CSV with a BOM so Excel shows Malay names and symbols correctly. */
    private function csv(string $filename, array $header, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            // Text starting with = + - @ would run as a formula in Excel, so prefix it with a quote.
            $safe = fn ($cell) => is_string($cell) && preg_match('/^[=+\-@\t\r]/', $cell) ? "'".$cell : $cell;
            $rows(fn (array $row) => fputcsv($out, array_map($safe, $row)));
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
