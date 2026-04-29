<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserImportModal extends Component
{
    use WithFileUploads;

    public bool $show = false;

    /**
     * Uploaded Excel file.
     *
     * Tipe sengaja tidak di-type-hint supaya kompatibel dengan
     * Livewire TemporaryUploadedFile.
     */
    public $file = null;

    public bool $showPreview = false;

    public int $totalRows = 0;

    public int $validRows = 0;

    public int $invalidRows = 0;

    /**
     * Preview data: each item has
     * row, name, email, role, division, valid, reason.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $previewData = [];

    protected function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ];
    }

    #[On('open-user-import')]
    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['file', 'showPreview', 'totalRows', 'validRows', 'invalidRows', 'previewData']);
        $this->show = true;
    }

    public function downloadTemplate()
    {
        $path = resource_path('import-templates/Users.xlsx');

        if (! file_exists($path)) {
            abort(404, 'Template tidak ditemukan');
        }

        return response()->download($path, 'Users_template.xlsx');
    }

    public function previewImport(): void
    {
        $this->validate();

        $this->reset(['showPreview', 'totalRows', 'validRows', 'invalidRows', 'previewData']);

        $filePath = $this->file->getRealPath();

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Read header row (row 1) to map columns.
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
            $headers[$col] = strtolower($value);
        }

        $this->totalRows = max(0, $highestRow - 1);

        $knownRoles = ['admin', 'director', 'hod', 'manager', 'iso'];
        $divisionNames = Division::pluck('name')->map(fn ($name) => strtolower($name))->all();

        for ($row = 2; $row <= $highestRow; $row++) {
            $name = trim((string) $sheet->getCellByColumnAndRow(array_search('name', $headers, true) ?: 1, $row)->getValue());
            $email = trim((string) $sheet->getCellByColumnAndRow(array_search('email', $headers, true) ?: 2, $row)->getValue());
            $role = trim(strtolower((string) $sheet->getCellByColumnAndRow(array_search('role', $headers, true) ?: 3, $row)->getValue()));
            $division = trim((string) $sheet->getCellByColumnAndRow(array_search('division name', $headers, true) ?: 4, $row)->getValue());
            $password = (string) $sheet->getCellByColumnAndRow(array_search('password', $headers, true) ?: 5, $row)->getValue();

            if ($name === '' && $email === '' && $role === '' && $division === '' && $password === '') {
                continue;
            }

            $rowReason = [];

            if ($name === '') {
                $rowReason[] = 'Nama kosong';
            }

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowReason[] = 'Email tidak valid';
            } elseif (User::where('email', $email)->exists()) {
                $rowReason[] = 'Email sudah digunakan';
            }

            if (! in_array($role, $knownRoles, true)) {
                $rowReason[] = 'Role tidak dikenal';
            }

            $divisionValid = true;
            if ($division !== '') {
                $divisionValid = in_array(strtolower($division), $divisionNames, true);
                if (! $divisionValid) {
                    $rowReason[] = 'Divisi tidak ditemukan';
                }
            }

            if ($password === '' || strlen($password) < 8) {
                $rowReason[] = 'Password minimal 8 karakter';
            }

            $valid = empty($rowReason);

            if ($valid) {
                $this->validRows++;
            } else {
                $this->invalidRows++;
            }

            $this->previewData[] = [
                'row' => $row,
                'name' => $name,
                'email' => $email,
                'role' => $role ?: '-',
                'division' => $division,
                'password' => $password,
                'valid' => $valid,
                'reason' => implode(', ', $rowReason),
            ];
        }

        $this->showPreview = true;
    }

    public function applyImport(): void
    {
        if (! $this->showPreview || $this->validRows === 0) {
            return;
        }

        $divisions = Division::all()
            ->mapWithKeys(fn ($d) => [strtolower($d->name) => $d->id])
            ->all();

        foreach ($this->previewData as $row) {
            if (! $row['valid']) {
                continue;
            }

            $divisionId = null;
            if ($row['division'] !== '') {
                $key = strtolower($row['division']);
                $divisionId = $divisions[$key] ?? null;
            }

            User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'role' => $row['role'],
                    'division_id' => $divisionId,
                    'status' => 'active',
                    'password' => $row['password'],
                ],
            );
        }

        $this->show = false;
        $this->dispatch('user-saved');
    }

    public function render()
    {
        return view('livewire.admin.user-import-modal');
    }
}
