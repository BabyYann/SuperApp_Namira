<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Jadwal Pelajaran - {{ $classroom->name }}</title>
    <style>
        @page {
            margin: 1cm 1.5cm;
        }
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            font-size: 11px;
        }
        .header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 1rem;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        .header-logo img {
            width: 70px;
            height: 70px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
            color: #0d9488;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0 0;
            font-size: 10px;
            color: #666;
        }

        .meta {
            margin-bottom: 1.5rem;
            width: 100%;
        }
        .meta td {
            vertical-align: top;
            padding: 2px 0;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .schedule-table th {
            background-color: #f3f4f6;
            border: 1px solid #000;
            padding: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }
        .schedule-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
            height: 100px; /* Fixed height for consistency */
        }
        .schedule-item {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #ccc;
        }
        .schedule-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .time {
            font-weight: bold;
            font-size: 10px;
            color: #115e59; /* Namira Teal approx */
            display: block;
            margin-bottom: 2px;
        }
        .subject {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }
        .teacher {
            font-style: italic;
            color: #666;
            font-size: 10px;
        }
        .day-header {
            text-transform: uppercase;
        }
        .footer {
            margin-top: 3rem;
            width: 100%;
        }
        .signature {
            text-align: center;
            float: right;
            width: 200px;
        }
        .signature-line {
            margin-top: 5rem;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @if($unit && $unit->logo)
            <div class="header-logo">
                <img src="{{ public_path('storage/' . $unit->logo) }}" alt="Logo">
            </div>
            @endif
            <div class="header-text">
                <h1>YAYASAN NAMIRA SCHOOL</h1>
                <h2>{{ $unit->name ?? 'SATUAN PENDIDIKAN NAMIRA' }}</h2>
                <p>{{ $unit->address ?? 'Jalan Pasar 1 No. 58, Tanjung Sari, Medan Selayang' }}</p>
            </div>
        </div>
    </div>


    <table class="meta">
        <tr>
            <td width="15%"><strong>Kelas</strong></td>
            <td width="2%">:</td>
            <td width="33%">{{ $classroom->name }}</td>
            <td width="15%"><strong>Wali Kelas</strong></td>
            <td width="2%">:</td>
            <td width="33%">{{ $classroom->homeroomTeacher->full_name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Tahun Ajaran</strong></td>
            <td>:</td>
            <td>{{ $classroom->academicYear->name ?? '-' }}</td>
            <td><strong>Dicetak Pada</strong></td>
            <td>:</td>
            <td>{{ now()->translatedFormat('d F Y, H:i') }}</td>
        </tr>
    </table>

    <table class="schedule-table">
        <thead>
            <tr>
                @foreach($days as $day)
                    <th class="day-header" width="{{ 100/count($days) }}%">{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($days as $day)
                    <td>
                        @if(isset($schedule[$day]) && count($schedule[$day]) > 0)
                            @foreach($schedule[$day]->sortBy('start_time') as $item)
                                <div class="schedule-item">
                                    <span class="time">
                                        {{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }} - 
                                        {{ \Carbon\Carbon::parse($item->end_time)->format('H:i') }}
                                    </span>
                                    <span class="subject">{{ $item->subject->name }}</span>
                                    <span class="teacher">{{ $item->teacher->full_name }}</span>
                                </div>
                            @endforeach
                        @else
                            <div style="text-align: center; color: #999; padding-top: 2rem;">- Libur -</div>
                        @endif
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Medan, {{ now()->translatedFormat('d F Y') }}</p>
            <p>Kepala Sekolah</p>
            <div class="signature-line"></div>
            <p style="font-weight: bold; margin-top: 5px;">( ..................................... )</p>
        </div>
    </div>
</body>
</html>
