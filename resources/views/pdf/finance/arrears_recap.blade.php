<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Tunggakan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .details { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
        th { background-color: #eee; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rekapitulasi Tunggakan Siswa</h1>
        <p>Yayasan Namira - {{ $date }}</p>
    </div>

    <div class="details">
        <strong>Filter Kelas:</strong> {{ $filter_class }} <br>
        <strong>Total Siswa:</strong> {{ $students->count() }} orang
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">NIS</th>
                <th width="35%">Nama Siswa</th>
                <th width="15%">Kelas</th>
                <th width="30%">Total Tunggakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $student->nis }}</td>
                <td>{{ $student->name }}</td>
                <td class="text-center">{{ $student->classroom }}</td>
                <td class="text-right">Rp {{ number_format($student->total_arrears, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada data tunggakan.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-right">GRAND TOTAL</td>
                <td class="text-right">Rp {{ number_format($grand_total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
