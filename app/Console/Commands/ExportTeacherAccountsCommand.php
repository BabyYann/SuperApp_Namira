<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportTeacherAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:teacher-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekspor daftar akun login real guru TK & KB Namira Kraksaan dari Database Live ke PDF & Terminal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Mengambil data real unit TK & KB Kraksaan dari database live...");

        // Find TK Kraksaan & KB Kraksaan units from live DB
        $units = Unit::where(function ($q) {
            $q->where('name', 'LIKE', '%TK%')
              ->orWhere('name', 'LIKE', '%KB%')
              ->orWhere('name', 'LIKE', '%PAUD%')
              ->orWhere('name', 'LIKE', '%Playgroup%')
              ->orWhere('code', 'LIKE', '%TK%')
              ->orWhere('code', 'LIKE', '%KB%')
              ->orWhere('code', 'LIKE', '%PAUD%');
        })->get();

        if ($units->isEmpty()) {
            // Fallback: search all TK and KB units if Kraksaan filter is strict
            $units = Unit::where('name', 'LIKE', '%TK%')
                ->orWhere('name', 'LIKE', '%KB%')
                ->orWhere('name', 'LIKE', '%PAUD%')
                ->get();
        }

        if ($units->isEmpty()) {
            $this->error("❌ Unit TK / KB Kraksaan tidak ditemukan di database.");
            return 1;
        }

        $unitIds = $units->pluck('id')->toArray();
        $this->info("✔ Unit ditemukan: " . $units->pluck('name')->implode(', '));

        // Fetch teachers attached to these units
        $teachers = Teacher::whereIn('unit_id', $unitIds)
            ->with(['user', 'unit'])
            ->orderBy('unit_id')
            ->orderBy('full_name')
            ->get();

        // Fallback: if teachers table empty, fetch Users directly by unit_id
        if ($teachers->isEmpty()) {
            $users = User::whereIn('unit_id', $unitIds)->get();
            $this->info("Total akun user ditemukan: " . $users->count());
        } else {
            $this->info("Total data guru ditemukan: " . $teachers->count());
        }

        $groupedData = [];
        $tableRows = [];
        $no = 1;

        if ($teachers->isNotEmpty()) {
            foreach ($teachers as $t) {
                $unitName = $t->unit ? $t->unit->name : 'TK/KB Kraksaan';
                $email = $t->user ? $t->user->email : ($t->email ?? '-');
                $name = $t->full_name ?: ($t->user ? $t->user->name : '-');
                $niy = $t->nip ?: ($t->niy ?? '');
                $password = !empty($niy) ? $niy : 'guru123';
                $phone = $t->phone ?: '-';

                $groupedData[$unitName][] = [
                    'no' => count($groupedData[$unitName] ?? []) + 1,
                    'name' => $name,
                    'email' => $email,
                    'niy' => $niy,
                    'password' => $password,
                    'phone' => $phone,
                ];

                $tableRows[] = [
                    $no++,
                    $unitName,
                    $name,
                    $email,
                    $password,
                    $phone
                ];
            }
        } else {
            $users = User::whereIn('unit_id', $unitIds)->get();
            foreach ($users as $u) {
                $unitName = $u->unit ? $u->unit->name : 'TK/KB Kraksaan';
                $email = $u->email;
                $name = $u->name;
                $niy = $u->niy ?? '';
                $password = !empty($niy) ? $niy : 'guru123';
                $phone = '-';

                $groupedData[$unitName][] = [
                    'no' => count($groupedData[$unitName] ?? []) + 1,
                    'name' => $name,
                    'email' => $email,
                    'niy' => $niy,
                    'password' => $password,
                    'phone' => $phone,
                ];

                $tableRows[] = [
                    $no++,
                    $unitName,
                    $name,
                    $email,
                    $password,
                    $phone
                ];
            }
        }

        // 1. Display CLI Table
        $this->newLine();
        $this->table(
            ['No', 'Unit', 'Nama Guru', 'Email (Username)', 'Password Login', 'No. HP'],
            $tableRows
        );

        // 2. Generate PDF
        $this->info("\n📄 Memproses file PDF...");

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 20px; }
            body { font-family: sans-serif; font-size: 9.5px; color: #1e293b; margin: 0; padding: 5px; }
            .header { text-align: center; margin-bottom: 12px; border-bottom: 2.5px solid #009688; padding-bottom: 8px; }
            .title { font-size: 15px; font-weight: bold; color: #00796b; margin: 0; text-transform: uppercase; }
            .subtitle { font-size: 11px; color: #334155; margin-top: 3px; font-weight: bold; }
            
            .notice-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a; padding: 8px 12px; margin-bottom: 12px; border-radius: 6px; }
            .notice-title { font-weight: bold; color: #166534; font-size: 10px; margin-bottom: 3px; }
            .notice-text { color: #15803d; font-size: 9px; line-height: 1.3; }
            
            .unit-title { font-size: 11px; font-weight: bold; color: #0f766e; margin-top: 14px; margin-bottom: 6px; background: #ccfbf1; padding: 5px 10px; border-radius: 4px; border: 1px solid #99f6e4; }
            
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            th { background: #0d9488; color: #ffffff; text-align: left; padding: 5px 7px; font-size: 8.5px; text-transform: uppercase; }
            td { padding: 4.5px 7px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
            tr:nth-child(even) { background: #f8fafc; }
            
            .badge-niy { background: #dcfce7; color: #15803d; font-weight: bold; padding: 2px 5px; border-radius: 4px; font-family: monospace; border: 1px solid #bbf7d0; font-size: 8.5px; }
            .badge-default { background: #fef3c7; color: #b45309; font-weight: bold; padding: 2px 5px; border-radius: 4px; font-family: monospace; border: 1px solid #fde68a; font-size: 8.5px; }
        </style>
        </head>
        <body>

        <div class="header">
            <div class="title">SUPERAPP NAMIRA — DAFTAR AKUN LOGIN GURU LIVE</div>
            <div class="subtitle">Unit TK Namira Kraksaan & KB / PAUD Playgroup Namira Kraksaan</div>
        </div>

        <div class="notice-box">
            <div class="notice-title">🔑 KETENTUAN PASSWORD DEFAULT LOGIN GURU:</div>
            <div class="notice-text">
                1. Jika akun memiliki <strong>NIY (Nomor Induk Yayasan)</strong>, maka <strong>Password = NIY</strong>.<br>
                2. Jika akun <strong>TIDAK memiliki NIY</strong> (akun default/baru), maka <strong>Password = guru123</strong>.
            </div>
        </div>';

        foreach ($groupedData as $uName => $items) {
            $html .= '<div class="unit-title">🏫 UNIT: ' . htmlspecialchars(strtoupper($uName)) . ' (' . count($items) . ' GURU)</div>';
            $html .= '<table>
            <thead>
            <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 32%;">Nama Guru</th>
            <th style="width: 33%;">Email / Username Login</th>
            <th style="width: 15%;">Password Login</th>
            <th style="width: 15%;">No. WhatsApp</th>
            </tr>
            </thead>
            <tbody>';

            foreach ($items as $item) {
                $badgeClass = ($item['password'] !== 'guru123') ? 'badge-niy' : 'badge-default';
                $html .= '<tr>
                <td>' . $item['no'] . '</td>
                <td>' . htmlspecialchars($item['name']) . '</td>
                <td>' . htmlspecialchars($item['email']) . '</td>
                <td><span class="' . $badgeClass . '">' . htmlspecialchars($item['password']) . '</span></td>
                <td>' . htmlspecialchars($item['phone']) . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfDir = public_path('downloads');
        if (!file_exists($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }

        $pdfPath = $pdfDir . '/Daftar_Akun_Guru_TK_dan_KB_Namira_Kraksaan.pdf';
        file_put_contents($pdfPath, $dompdf->output());

        $this->newLine();
        $this->info("✅ BERHASIL EXPORT AKUN REAL GURU!");
        $this->info("📁 File PDF tersimpan di: " . $pdfPath);
        $this->info("🔗 Link Download PDF Live: https://namiraschool.com/downloads/Daftar_Akun_Guru_TK_dan_KB_Namira_Kraksaan.pdf");

        return 0;
    }
}
