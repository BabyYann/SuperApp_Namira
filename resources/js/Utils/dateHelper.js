/**
 * Konversi nilai tanggal (YYYY-MM-DD atau ISO timestamp UTC) ke format YYYY-MM-DD
 * tanpa pergeseran mundur 1 hari akibat zona waktu UTC vs Asia/Jakarta (UTC+7).
 */
export function toDateInputValue(val) {
    if (!val) return '';
    const str = String(val).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        return str;
    }

    // Jika format "YYYY-MM-DD HH:mm:ss" tanpa timezone Z/offset, ambil bagian tanggalnya
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(str)) {
        return str.substring(0, 10);
    }

    // Jika format ISO UTC seperti "2021-03-04T17:00:00.000000Z", konversi ke Asia/Jakarta
    const d = new Date(str);
    if (!isNaN(d.getTime())) {
        try {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Jakarta',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).formatToParts(d);
            const y = parts.find((p) => p.type === 'year')?.value;
            const m = parts.find((p) => p.type === 'month')?.value;
            const day = parts.find((p) => p.type === 'day')?.value;
            if (y && m && day) {
                return `${y}-${m}-${day}`;
            }
        } catch (e) {
            // Fallback ke local date components
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }
    }

    return str.substring(0, 10);
}

/**
 * Format tanggal kalender ke Bahasa Indonesia tanpa pergeseran hari zona waktu.
 */
export function formatIndonesianDate(
    val,
    options = { day: 'numeric', month: 'long', year: 'numeric' }
) {
    if (!val) return '';
    const ymd = toDateInputValue(val);
    const match = ymd.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return String(val);

    const year = Number(match[1]);
    const month = Number(match[2]) - 1;
    const day = Number(match[3]);
    // Gunakan jam 12:00 siang lokal agar aman dari pergeseran DST/UTC di browser mana pun
    const safeLocalDate = new Date(year, month, day, 12, 0, 0);
    if (isNaN(safeLocalDate.getTime())) return ymd;

    try {
        return new Intl.DateTimeFormat('id-ID', options).format(safeLocalDate);
    } catch (e) {
        return ymd;
    }
}
