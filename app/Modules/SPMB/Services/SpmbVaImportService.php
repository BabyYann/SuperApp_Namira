<?php

namespace App\Modules\SPMB\Services;

use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SpmbVaImportService
{
    /**
     * Determine whether the unit uses 100% direct payment (no DP split).
     */
    public function isFullPaymentOnly(?SpmbSetting $setting): bool
    {
        return (int) ($setting?->min_down_payment_percentage ?? 60) >= 100;
    }

    /**
     * Build an Excel (.xlsx) VA import template pre-filled with unit applicants.
     */
    public function buildTemplateSpreadsheet(Unit $targetUnit, ?SpmbSetting $setting, Collection $applicants): Spreadsheet
    {
        $isFullPaymentOnly = $this->isFullPaymentOnly($setting);
        $defaultFee = (float) ($setting?->admission_fee_total ?? 8500000);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Input VA');

        $headers = [
            'No. Registrasi',
            'Nama Calon Siswa',
            'Jalur / Kategori',
            'Status',
            'No. Virtual Account',
            'Total Biaya Masuk (Rp)',
        ];

        if ($isFullPaymentOnly) {
            $headers[] = 'Batas Bayar Lunas 100% (YYYY-MM-DD)';
            $vaEditableCols = ['E', 'F', 'G'];
            $lastCol = 'G';
        } else {
            $headers[] = 'Batas Bayar DP 60% (YYYY-MM-DD)';
            $headers[] = 'Batas Bayar Lunas 100% (YYYY-MM-DD)';
            $vaEditableCols = ['E', 'F', 'G', 'H'];
            $lastCol = 'H';
        }

        foreach ($headers as $idx => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$colLetter}1", $label);
        }

        // Style header row (dark emerald for read-only reference cols, amber highlight for VA input cols)
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064E3B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        foreach ($vaEditableCols as $col) {
            $sheet->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B45309');
        }
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowNum = 2;
        foreach ($applicants as $app) {
            $fee = ((float) ($app->total_admission_fee ?? 0) > 0)
                ? (float) $app->total_admission_fee
                : $defaultFee;

            $sheet->setCellValueExplicit("A{$rowNum}", (string) $app->registration_number, DataType::TYPE_STRING);
            $sheet->setCellValue("B{$rowNum}", (string) $app->full_name);
            $sheet->setCellValue("C{$rowNum}", (string) ($app->category_label ?? $app->category ?? '-'));
            $sheet->setCellValue("D{$rowNum}", (string) ($app->status_label ?? $app->status ?? '-'));
            $sheet->setCellValueExplicit("E{$rowNum}", (string) ($app->virtual_account_number ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("F{$rowNum}", $fee);

            if ($isFullPaymentOnly) {
                $sheet->setCellValueExplicit(
                    "G{$rowNum}",
                    $app->full_payment_deadline ? $app->full_payment_deadline->format('Y-m-d') : '',
                    DataType::TYPE_STRING
                );
            } else {
                $sheet->setCellValueExplicit(
                    "G{$rowNum}",
                    $app->down_payment_deadline ? $app->down_payment_deadline->format('Y-m-d') : '',
                    DataType::TYPE_STRING
                );
                $sheet->setCellValueExplicit(
                    "H{$rowNum}",
                    $app->full_payment_deadline ? $app->full_payment_deadline->format('Y-m-d') : '',
                    DataType::TYPE_STRING
                );
            }

            $rowNum++;
        }

        $maxFormattedRow = max($rowNum + 50, 200);
        // Lock VA and Registration columns to Text format so leading zeros / 16+ digits stay intact in Excel
        $sheet->getStyle("A2:A{$maxFormattedRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("E2:E{$maxFormattedRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("F2:F{$maxFormattedRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("G2:{$lastCol}{$maxFormattedRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        if ($rowNum > 2) {
            $lastDataRow = $rowNum - 1;
            $sheet->getStyle("A2:{$lastCol}{$lastDataRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            // Subtle warm tint on editable VA column
            $sheet->getStyle("E2:E{$lastDataRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FFFBEB');
        }

        $sheet->freezePane('A2');

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Ensure minimum comfortable width for VA column
        $sheet->getColumnDimension('E')->setAutoSize(false);
        $sheet->getColumnDimension('E')->setWidth(26);

        // Instruction sheet
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Petunjuk Pengisian');
        $guideLines = [
            ['PANDUAN IMPORT VIRTUAL ACCOUNT (VA) SPMB - '.strtoupper($targetUnit->name ?? 'NAMIRA')],
            ['1. Jangan mengubah isi kolom A (No. Registrasi) karena digunakan sebagai kunci pencocokan siswa.'],
            ['2. Isi kolom E (No. Virtual Account). Kolom ini sudah diformat TEXT agar angka 16 digit tidak terpotong atau berubah menjadi E+ di Excel.'],
            ['3. Baris yang kolom No. Virtual Account-nya dikosongkan akan otomatis DILEWATI saat upload.'],
            ['4. Kolom F (Total Biaya Masuk) sudah diisi tarif default unit. Anda boleh mengubah angkanya per anak (misal penerima beasiswa/potongan).'],
            ['5. Kolom tanggal batas bayar menggunakan format YYYY-MM-DD (contoh: 2026-12-15). Boleh dikosongkan di Excel lalu diisi 1x lewat form Upload jika tanggal semua siswa sama.'],
            ['6. Simpan file dalam format .xlsx lalu upload kembali melalui tombol "Import VA Massal" di dashboard SPMB Admin.'],
        ];
        foreach ($guideLines as $i => $line) {
            $guideSheet->setCellValue('A'.($i + 1), $line[0]);
        }
        $guideSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $guideSheet->getColumnDimension('A')->setWidth(110);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Parse and validate uploaded XLSX/XLS/CSV VA file without persisting changes.
     */
    public function parseAndValidateFile(
        UploadedFile $file,
        Unit $targetUnit,
        ?SpmbSetting $setting,
        ?string $globalDpDeadline = null,
        ?string $globalFullDeadline = null
    ): array {
        $spreadsheet = $this->loadSpreadsheet($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        if ($highestRow < 2) {
            throw new InvalidArgumentException('File tidak memiliki baris data (minimal header + 1 baris siswa).');
        }

        $headerMap = $this->detectHeaderColumns($sheet, $highestColIndex);
        if ($headerMap['reg'] === null || $headerMap['va'] === null) {
            throw new InvalidArgumentException(
                'Header kolom wajib ("No. Registrasi" dan "No. Virtual Account") tidak ditemukan di baris pertama. Pastikan Anda memakai template resmi.'
            );
        }

        $isFullPaymentOnly = $this->isFullPaymentOnly($setting);
        $defaultFee = (float) ($setting?->admission_fee_total ?? 8500000);

        $parsedRawRows = [];
        $regNumbers = [];

        for ($r = 2; $r <= $highestRow; $r++) {
            $regRaw = $this->extractStringCell($sheet->getCell([$headerMap['reg'], $r]));
            $vaCheck = $this->extractVaNumber($sheet->getCell([$headerMap['va'], $r]));
            $nameRaw = $headerMap['name'] ? trim((string) $sheet->getCell([$headerMap['name'], $r])->getFormattedValue()) : '';

            // Skip completely blank trailing rows
            if ($regRaw === '' && $vaCheck['value'] === '' && $vaCheck['error'] === null && $nameRaw === '') {
                continue;
            }

            $feeParsed = $headerMap['fee']
                ? $this->extractFee($sheet->getCell([$headerMap['fee'], $r]))
                : ['value' => null, 'error' => null];
            $dpParsed = (! $isFullPaymentOnly && $headerMap['dp_deadline'])
                ? $this->extractDate($sheet->getCell([$headerMap['dp_deadline'], $r]))
                : ['value' => null, 'error' => null];
            $fullParsed = $headerMap['full_deadline']
                ? $this->extractDate($sheet->getCell([$headerMap['full_deadline'], $r]))
                : ['value' => null, 'error' => null];

            $parsedRawRows[] = [
                'row_number' => $r,
                'registration_number' => $regRaw,
                'excel_name' => $nameRaw,
                'va_value' => $vaCheck['value'],
                'va_error' => $vaCheck['error'],
                'fee_parsed' => $feeParsed,
                'dp_deadline_parsed' => $dpParsed,
                'full_deadline_parsed' => $fullParsed,
            ];

            if ($regRaw !== '') {
                $regNumbers[] = $regRaw;
            }
        }

        if (empty($parsedRawRows)) {
            throw new InvalidArgumentException('Tidak ada baris data yang terbaca pada file.');
        }

        $applicantsByReg = SpmbApplicant::where('unit_id', $targetUnit->id)
            ->whereIn('registration_number', array_values(array_unique($regNumbers)))
            ->get()
            ->keyBy(fn ($item) => strtoupper(trim($item->registration_number)));

        $seenRegs = [];
        $rows = [];
        $summary = [
            'total_rows' => 0,
            'new_count' => 0,
            'overwrite_count' => 0,
            'unchanged_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
        ];

        foreach ($parsedRawRows as $item) {
            $summary['total_rows']++;
            $regKey = strtoupper(trim($item['registration_number']));
            $applicant = $regKey !== '' ? $applicantsByReg->get($regKey) : null;

            $errors = [];
            if ($regKey === '') {
                $errors[] = 'No. Registrasi kosong.';
            } elseif (! $applicant) {
                $errors[] = "No. Registrasi '{$item['registration_number']}' tidak ditemukan di unit {$targetUnit->name}.";
            } elseif (isset($seenRegs[$regKey])) {
                $errors[] = "No. Registrasi duplikat di file (sudah muncul di baris {$seenRegs[$regKey]}).";
            }

            if ($regKey !== '' && ! isset($seenRegs[$regKey])) {
                $seenRegs[$regKey] = $item['row_number'];
            }

            if ($item['va_error'] !== null) {
                $errors[] = $item['va_error'];
            }
            if ($item['fee_parsed']['error'] !== null) {
                $errors[] = $item['fee_parsed']['error'];
            }
            if ($item['dp_deadline_parsed']['error'] !== null) {
                $errors[] = $item['dp_deadline_parsed']['error'];
            }
            if ($item['full_deadline_parsed']['error'] !== null) {
                $errors[] = $item['full_deadline_parsed']['error'];
            }

            // Blank VA on a valid row -> skip cleanly unless there is already a registration error
            if (empty($errors) && $item['va_value'] === '') {
                $summary['skipped_count']++;
                $rows[] = [
                    'row_number' => $item['row_number'],
                    'applicant_id' => $applicant->id,
                    'registration_number' => $applicant->registration_number,
                    'full_name' => $applicant->full_name,
                    'existing_va' => $applicant->virtual_account_number,
                    'new_va' => '',
                    'total_admission_fee' => (float) ($applicant->total_admission_fee > 0 ? $applicant->total_admission_fee : $defaultFee),
                    'down_payment_deadline' => $isFullPaymentOnly ? null : $applicant->down_payment_deadline?->format('Y-m-d'),
                    'full_payment_deadline' => $applicant->full_payment_deadline?->format('Y-m-d'),
                    'action' => 'skip',
                    'message' => 'No. VA kosong (dilewati)',
                ];

                continue;
            }

            if (! empty($errors)) {
                $summary['error_count']++;
                $rows[] = [
                    'row_number' => $item['row_number'],
                    'applicant_id' => $applicant?->id,
                    'registration_number' => $item['registration_number'] ?: '-',
                    'full_name' => $applicant?->full_name ?: ($item['excel_name'] ?: '-'),
                    'existing_va' => $applicant?->virtual_account_number,
                    'new_va' => $item['va_value'],
                    'total_admission_fee' => null,
                    'down_payment_deadline' => null,
                    'full_payment_deadline' => null,
                    'action' => 'error',
                    'message' => implode(' ', $errors),
                ];

                continue;
            }

            // Resolve final fee & deadlines (Excel cell > global modal deadline > existing applicant value > setting default)
            $finalFee = $item['fee_parsed']['value']
                ?? (((float) ($applicant->total_admission_fee ?? 0) > 0) ? (float) $applicant->total_admission_fee : $defaultFee);

            $finalDpDeadline = $isFullPaymentOnly
                ? null
                : ($item['dp_deadline_parsed']['value']
                    ?? ($globalDpDeadline ?: $applicant->down_payment_deadline?->format('Y-m-d')));

            $finalFullDeadline = $item['full_deadline_parsed']['value']
                ?? ($globalFullDeadline ?: $applicant->full_payment_deadline?->format('Y-m-d'));

            $existingVa = trim((string) ($applicant->virtual_account_number ?? ''));
            $isSameRecord = $existingVa === $item['va_value']
                && abs((float) ($applicant->total_admission_fee ?? 0) - $finalFee) < 0.01
                && ($applicant->down_payment_deadline?->format('Y-m-d') === $finalDpDeadline)
                && ($applicant->full_payment_deadline?->format('Y-m-d') === $finalFullDeadline);

            if ($isSameRecord) {
                $action = 'unchanged';
                $message = 'Sudah sesuai (tidak berubah)';
                $summary['unchanged_count']++;
            } elseif ($existingVa !== '') {
                $action = 'overwrite';
                $message = "Timpa VA lama ({$existingVa})";
                $summary['overwrite_count']++;
            } else {
                $action = 'new';
                $message = 'Siap simpan VA baru';
                $summary['new_count']++;
            }

            $rows[] = [
                'row_number' => $item['row_number'],
                'applicant_id' => $applicant->id,
                'registration_number' => $applicant->registration_number,
                'full_name' => $applicant->full_name,
                'existing_va' => $existingVa !== '' ? $existingVa : null,
                'new_va' => $item['va_value'],
                'total_admission_fee' => $finalFee,
                'down_payment_deadline' => $finalDpDeadline,
                'full_payment_deadline' => $finalFullDeadline,
                'action' => $action,
                'message' => $message,
            ];
        }

        return [
            'is_full_payment_only' => $isFullPaymentOnly,
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /**
     * Persist validated VA import rows atomically for the target unit.
     */
    public function commitRows(
        array $rows,
        Unit $targetUnit,
        ?SpmbSetting $setting,
        bool $skipExisting = false
    ): array {
        $isFullPaymentOnly = $this->isFullPaymentOnly($setting);
        $dpPercentage = (int) ($setting?->min_down_payment_percentage ?? 60);

        $updatedNew = 0;
        $updatedOverwrite = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $rows,
            $targetUnit,
            $isFullPaymentOnly,
            $dpPercentage,
            $skipExisting,
            &$updatedNew,
            &$updatedOverwrite,
            &$skipped
        ) {
            $regNumbers = collect($rows)
                ->pluck('registration_number')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $applicantsByReg = SpmbApplicant::where('unit_id', $targetUnit->id)
                ->whereIn('registration_number', $regNumbers)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($item) => strtoupper(trim($item->registration_number)));

            foreach ($rows as $row) {
                $regKey = strtoupper(trim((string) ($row['registration_number'] ?? '')));
                $vaNumber = trim((string) ($row['new_va'] ?? ''));

                if ($regKey === '' || $vaNumber === '') {
                    $skipped++;

                    continue;
                }

                $applicant = $applicantsByReg->get($regKey);
                if (! $applicant) {
                    $skipped++;

                    continue;
                }

                $hadVa = trim((string) ($applicant->virtual_account_number ?? '')) !== '';
                if ($skipExisting && $hadVa) {
                    $skipped++;

                    continue;
                }

                $fee = (float) ($row['total_admission_fee'] ?? 0);
                $minDownPayment = $isFullPaymentOnly
                    ? $fee
                    : round($fee * ($dpPercentage / 100), 2);

                $applicant->update([
                    'virtual_account_number' => $vaNumber,
                    'total_admission_fee' => $fee,
                    'min_down_payment' => $minDownPayment,
                    'down_payment_deadline' => $isFullPaymentOnly ? null : ($row['down_payment_deadline'] ?: null),
                    'full_payment_deadline' => $row['full_payment_deadline'] ?: null,
                ]);

                if ($hadVa) {
                    $updatedOverwrite++;
                } else {
                    $updatedNew++;
                }
            }
        });

        return [
            'updated_new' => $updatedNew,
            'updated_overwrite' => $updatedOverwrite,
            'total_updated' => $updatedNew + $updatedOverwrite,
            'skipped' => $skipped,
        ];
    }

    /**
     * Load XLSX/XLS/CSV file cleanly, including semicolon-delimited Indonesian Excel CSVs.
     */
    protected function loadSpreadsheet(UploadedFile $file): Spreadsheet
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getPathname();

        if ($ext === 'csv' || $ext === 'txt') {
            $sample = (string) file_get_contents($path, false, null, 0, 2048);
            $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
            $reader = new CsvReader;
            $reader->setDelimiter($delimiter);
            $reader->setInputEncoding('UTF-8');

            return $reader->load($path);
        }

        return IOFactory::load($path);
    }

    /**
     * Map column indexes dynamically from header labels in row 1.
     */
    protected function detectHeaderColumns($sheet, int $highestColIndex): array
    {
        $map = [
            'reg' => null,
            'name' => null,
            'va' => null,
            'fee' => null,
            'dp_deadline' => null,
            'full_deadline' => null,
        ];

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $rawHeader = trim((string) $sheet->getCell([$col, 1])->getFormattedValue());
            $h = strtolower(preg_replace('/\s+/', ' ', $rawHeader));
            if ($h === '') {
                continue;
            }

            if ($map['reg'] === null && (str_contains($h, 'registrasi') || str_contains($h, 'registration'))) {
                $map['reg'] = $col;
            } elseif ($map['va'] === null && (str_contains($h, 'virtual account') || preg_match('/\b(no\.?\s*)?va\b/', $h))) {
                $map['va'] = $col;
            } elseif ($map['name'] === null && (str_contains($h, 'nama') || str_contains($h, 'name'))) {
                $map['name'] = $col;
            } elseif ($map['fee'] === null && (str_contains($h, 'biaya') || str_contains($h, 'nominal') || str_contains($h, 'fee'))) {
                $map['fee'] = $col;
            } elseif ($map['dp_deadline'] === null && (str_contains($h, 'dp') || str_contains($h, '60%'))) {
                $map['dp_deadline'] = $col;
            } elseif ($map['full_deadline'] === null && (str_contains($h, 'lunas') || str_contains($h, '100%') || str_contains($h, 'batas bayar'))) {
                $map['full_deadline'] = $col;
            }
        }

        return $map;
    }

    protected function extractStringCell(Cell $cell): string
    {
        $val = trim((string) $cell->getFormattedValue());

        return ltrim($val, "'");
    }

    /**
     * Extract VA number safely and reject Excel scientific-notation corruption (e.g. 9.88E+15).
     */
    protected function extractVaNumber(Cell $cell): array
    {
        $raw = $cell->getValue();
        $formatted = trim((string) $cell->getFormattedValue());

        if ($raw === null || $formatted === '') {
            return ['value' => '', 'error' => null];
        }

        $candidate = is_string($raw) ? trim($raw) : $formatted;
        $candidate = ltrim($candidate, "'");
        $candidate = preg_replace('/\s+/', '', $candidate);

        if ($candidate === '' || $candidate === '-') {
            return ['value' => '', 'error' => null];
        }

        if (preg_match('/e\+/i', $formatted) || (is_string($raw) && preg_match('/e\+/i', $raw))) {
            return [
                'value' => $formatted,
                'error' => "Format No. VA '{$formatted}' rusak menjadi notasi ilmiah (E+). Format kolom sebagai Text di Excel sebelum mengisi ulang.",
            ];
        }

        if (strlen($candidate) > 50) {
            return [
                'value' => $candidate,
                'error' => 'No. VA melebihi 50 karakter.',
            ];
        }

        return ['value' => $candidate, 'error' => null];
    }

    protected function extractFee(?Cell $cell): array
    {
        if ($cell === null) {
            return ['value' => null, 'error' => null];
        }

        $raw = $cell->getValue();
        $formatted = trim((string) $cell->getFormattedValue());
        if ($raw === null || $formatted === '' || $formatted === '-') {
            return ['value' => null, 'error' => null];
        }

        if (is_int($raw) || is_float($raw)) {
            if ($raw < 0) {
                return ['value' => null, 'error' => 'Total biaya tidak boleh negatif.'];
            }

            return ['value' => (float) $raw, 'error' => null];
        }

        $clean = preg_replace('/[Rp\s]/i', '', $formatted);
        // Handle Indonesian formatting like 8.500.000 or 8.500.000,00 vs 8,500,000.00
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $clean)) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $clean)) {
            $clean = str_replace(',', '', $clean);
        } else {
            $clean = str_replace(',', '.', $clean);
        }

        if (! is_numeric($clean) || (float) $clean < 0) {
            return ['value' => null, 'error' => "Format Total Biaya '{$formatted}' tidak valid."];
        }

        return ['value' => (float) $clean, 'error' => null];
    }

    protected function extractDate(?Cell $cell): array
    {
        if ($cell === null) {
            return ['value' => null, 'error' => null];
        }

        $raw = $cell->getValue();
        $formatted = trim((string) $cell->getFormattedValue());
        if ($raw === null || $formatted === '' || $formatted === '-') {
            return ['value' => null, 'error' => null];
        }

        try {
            if (is_numeric($raw) && (ExcelDate::isDateTime($cell) || ($raw > 20000 && $raw < 80000))) {
                return [
                    'value' => ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d'),
                    'error' => null,
                ];
            }

            $text = ltrim($formatted, "'");
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
                $dt = Carbon::createFromFormat($fmt, $text);
                if ($dt && $dt->format($fmt) === $text) {
                    return ['value' => $dt->format('Y-m-d'), 'error' => null];
                }
            }

            return ['value' => Carbon::parse($text)->format('Y-m-d'), 'error' => null];
        } catch (\Throwable $e) {
            return ['value' => null, 'error' => "Format tanggal '{$formatted}' tidak valid (gunakan YYYY-MM-DD)."];
        }
    }
}
