<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Stiker QR Inventaris - Yayasan Namira</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 20px;
        }

        .no-print-bar {
            background: #ffffff;
            padding: 14px 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .no-print-bar h2 {
            font-size: 16px;
            font-weight: 700;
            color: #0d9488;
        }

        .btn {
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #0d9488;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #0f766e;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        /* Printable Sheet Container */
        .sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 8mm 6mm;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-auto-rows: minmax(26mm, auto);
            gap: 3mm;
            align-content: start;
        }

        /* Compact Sticker Item with Abstract Accent */
        .sticker-card {
            border: 1px solid #cbd5e1;
            border-left: 3.5px solid #0d9488;
            border-radius: 6px;
            padding: 4px 6px 4px 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            height: 26mm;
            overflow: hidden;
            position: relative;
            page-break-inside: avoid;
        }

        /* Abstract Geometric Corner Wave SVG Accent */
        .abstract-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 42px;
            height: 42px;
            pointer-events: none;
            opacity: 0.15;
            z-index: 1;
        }

        .abstract-dots {
            position: absolute;
            bottom: 2px;
            right: 42px;
            width: 24px;
            height: 12px;
            pointer-events: none;
            opacity: 0.08;
            z-index: 1;
        }

        /* Left QR Section */
        .sticker-qr {
            width: 20mm;
            height: 20mm;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            z-index: 2;
        }

        .sticker-qr svg {
            width: 100%;
            height: 100%;
        }

        /* Right Content Section */
        .sticker-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 20mm;
            overflow: hidden;
            z-index: 2;
            padding-right: 4px;
        }

        .unit-tag {
            font-size: 7pt;
            font-weight: 800;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .unit-tag::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 4px;
            background-color: #0d9488;
            border-radius: 50%;
        }

        .item-name {
            font-size: 8.5pt;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            text-transform: capitalize;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-top: 1px;
        }

        .item-location {
            font-size: 6pt;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-meta {
            font-size: 6.5pt;
            color: #475569;
            font-weight: 700;
            line-height: 1.1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .item-code {
            font-family: monospace;
            font-weight: 700;
            color: #1e293b;
            font-size: 6.5pt;
            letter-spacing: -0.2px;
        }

        .badge-source {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 5.5pt;
            font-weight: 800;
            background: #e0f2fe;
            color: #0369a1;
            border: 0.5px solid #bae6fd;
        }

        .badge-source-yys {
            background: #ccfbf1;
            color: #0f766e;
            border: 0.5px solid #99f6e4;
        }

        /* Print Media Overrides */
        @media print {
            body {
                background: none;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .sheet {
                width: 100%;
                margin: 0;
                padding: 4mm 4mm;
                box-shadow: none;
                border-radius: 0;
            }

            .sticker-card {
                border: 1px solid #94a3b8;
                border-left: 3.5px solid #0d9488 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .abstract-bg, .abstract-dots {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 portrait;
                margin: 4mm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <h2>🏷️ Cetak Stiker Inventaris (Stiker Kompak)</h2>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.history.back()" class="btn btn-secondary">Kembali</button>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak / Print Stiker</button>
        </div>
    </div>

    <div class="sheet">
        @forelse($items as $item)
            <div class="sticker-card">
                <!-- Abstract Geometric SVG Overlay (Top Right) -->
                <svg class="abstract-bg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 0 H100 V100 C60 80 40 40 0 0 Z" fill="#0d9488"/>
                    <circle cx="80" cy="20" r="12" fill="#0f766e"/>
                </svg>

                <!-- Abstract Dots Pattern Overlay (Bottom Accent) -->
                <svg class="abstract-dots" viewBox="0 0 50 25" fill="#0d9488" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="5" cy="5" r="2.5"/><circle cx="20" cy="5" r="2.5"/><circle cx="35" cy="5" r="2.5"/>
                    <circle cx="12.5" cy="18" r="2.5"/><circle cx="27.5" cy="18" r="2.5"/>
                </svg>

                <!-- Left QR Code -->
                <div class="sticker-qr">
                    {!! $item->qr_svg !!}
                </div>

                <!-- Right Information -->
                <div class="sticker-info">
                    <div>
                        <div class="unit-tag">{{ $item->unit->name ?? $unitName }}</div>
                        <div class="item-name">{{ $item->name }} @if($item->brand) ({{ $item->brand }}) @endif</div>
                        @if($item->room || $item->classroom)
                            <div class="item-location">📍 {{ $item->room->name ?? $item->classroom->name }}</div>
                        @endif
                    </div>
                    <div class="item-meta">
                        <span class="item-code">{{ $item->code }}</span>
                        <span class="badge-source {{ $item->funding_source == 'YYS' ? 'badge-source-yys' : '' }}">
                            {{ $item->funding_source == 'BOS' ? 'BOS' : 'YYS' }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: span 3; text-align: center; padding: 40px; color: #94a3b8;">
                Tidak ada data inventaris yang dipilih untuk dicetak.
            </div>
        @endforelse
    </div>

</body>
</html>
