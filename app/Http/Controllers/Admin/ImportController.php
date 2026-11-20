<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The coordinator adds many accounts at once from a CSV file (Excel: File > Save As > CSV UTF-8).
 */
class ImportController extends Controller
{
    private const COLUMNS = ['username', 'name', 'email', 'role', 'supervisor', 'password'];

    private const MAX_ROWS = 2000;

    public function create(): View
    {
        return view('admin.import', ['results' => null]);
    }

    /** An example file with the right columns. */
    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            fputcsv($out, ['A192920', 'Siti Aminah binti Ali', 'a192920@siswa.ukm.edu.my', 'student', 'dr.aisyah', '']);
            fputcsv($out, ['dr.lee', 'Dr. Lee Chong Wei', 'lee@ukm.edu.my', 'supervisor', '', '']);
            fclose($out);
        }, 'logbook-users-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request): View
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']], [], ['file' => 'CSV file']);

        $rows = $this->read($request->file('file')->getRealPath());
        $supervisors = User::query()->where('role', User::ROLE_SUPERVISOR)->pluck('id', 'username')->mapWithKeys(fn ($id, $u) => [Str::lower($u) => $id]);

        $created = [];
        $skipped = [];
        $seen = [];

        DB::transaction(function () use ($rows, $supervisors, &$created, &$skipped, &$seen) {
            foreach ($rows as $line => $row) {
                $role = Str::lower($row['role'] ?: User::ROLE_STUDENT);
                $username = trim($row['username']);
                if ($role === User::ROLE_STUDENT) {
                    $username = Str::upper($username);
                }

                $validator = Validator::make([...$row, 'username' => $username, 'role' => $role], [
                    'username' => ['required', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')],
                    'name' => ['required', 'max:255'],
                    'email' => ['nullable', 'email', 'max:255', Rule::unique('users')],
                    'role' => ['required', Rule::in(User::ROLES)],
                    'password' => ['nullable', 'min:8'],
                ]);

                $supervisorKey = Str::lower(trim($row['supervisor']));
                $errors = $validator->errors()->all();
                if (isset($seen[Str::lower($username)])) {
                    $errors[] = 'This username appears twice in the file.';
                }
                if ($role === User::ROLE_STUDENT && $supervisorKey !== '' && ! $supervisors->has($supervisorKey)) {
                    $errors[] = "Supervisor \"{$row['supervisor']}\" was not found.";
                }

                if ($errors) {
                    $skipped[] = ['line' => $line, 'username' => $username ?: '—', 'reason' => implode(' ', $errors)];

                    continue;
                }

                $seen[Str::lower($username)] = true;
                $password = $row['password'] ?: Str::password(10, symbols: false);

                User::create([
                    'username' => $username,
                    'name' => trim($row['name']),
                    'email' => $row['email'] ?: null,
                    'role' => $role,
                    'supervisor_id' => $role === User::ROLE_STUDENT ? $supervisors->get($supervisorKey) : null,
                    'password' => $password,
                ]);

                $created[] = [
                    'username' => $username,
                    'name' => trim($row['name']),
                    'role' => $role,
                    // Only show passwords we made up; ones from the file are already known.
                    'password' => $row['password'] ? null : $password,
                ];
            }
        });

        return view('admin.import', ['results' => ['created' => $created, 'skipped' => $skipped, 'total' => count($rows)]]);
    }

    /**
     * Read the CSV into rows keyed by column name and file line number. Header names are matched loosely
     * ("Matric No" or "username", "Nama" or "name") so a hand-made Excel sheet still works.
     *
     * @return array<int, array<string, string>>
     */
    private function read(string $path): array
    {
        $aliases = [
            'username' => ['username', 'matric', 'matricno', 'nomatrik', 'matrik', 'staffid', 'id'],
            'name' => ['name', 'fullname', 'nama', 'namapenuh'],
            'email' => ['email', 'emel', 'e-mail'],
            'role' => ['role', 'peranan', 'type'],
            'supervisor' => ['supervisor', 'supervisorusername', 'penyelia'],
            'password' => ['password', 'katalaluan'],
        ];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle) ?: [];
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
        $normal = array_map(fn ($h) => preg_replace('/[^a-z-]/', '', Str::lower((string) $h)), $header);

        $index = [];
        foreach ($aliases as $column => $names) {
            $found = array_search(true, array_map(fn ($h) => in_array($h, $names, true), $normal), true);
            $index[$column] = $found === false ? null : $found;
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false && count($rows) < self::MAX_ROWS) {
            $line++;
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[$line] = array_map(fn ($i) => $i === null ? '' : trim((string) ($data[$i] ?? '')), $index);
        }
        fclose($handle);

        return $rows;
    }
}
