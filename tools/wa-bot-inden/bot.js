import fs from 'node:fs';
import path from 'node:path';
import readline from 'node:readline/promises';
import { stdin as input, stdout as output } from 'node:process';
import { fileURLToPath } from 'node:url';
import * as baileys from '@whiskeysockets/baileys';
import pinoPkg from 'pino';
import qrcodePkg from 'qrcode-terminal';

const makeWASocket = baileys.default?.default || baileys.default || baileys.makeWASocket;
const { useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, Browsers } = baileys;
const pino = pinoPkg.default || pinoPkg;
const qrcode = qrcodePkg.default || qrcodePkg;

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const CONFIG_PATH = path.join(__dirname, 'config.json');
const DATA_PATH = path.join(__dirname, 'data_inden_kraksaan.json');
const LOG_PATH = path.join(__dirname, 'sent_log.json');
const AUTH_DIR = path.join(__dirname, 'auth_session');

const args = new Set(process.argv.slice(2));
const isTestMode = args.has('--test');
const isDryRun = args.has('--dry-run');
const isStatusMode = args.has('--status');
const isExportSisa = args.has('--export-sisa');
const isResetLog = args.has('--reset-log');
const isLogout = args.has('--logout');

// ============================================================================
// Helpers
// ============================================================================

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const randomInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

const pickRandom = (arr) => arr[Math.floor(Math.random() * arr.length)];

function normalizeTo62(rawPhone) {
    let digits = String(rawPhone || '').replace(/[^0-9]/g, '');
    if (digits.startsWith('0')) {
        digits = '62' + digits.slice(1);
    } else if (digits.startsWith('8')) {
        digits = '62' + digits;
    }
    return digits;
}

function formatLocal08(phone62) {
    return phone62.startsWith('62') ? '0' + phone62.slice(2) : phone62;
}

function loadConfig() {
    if (!fs.existsSync(CONFIG_PATH)) {
        throw new Error(`File config.json tidak ditemukan di ${CONFIG_PATH}`);
    }
    return JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf-8'));
}

function saveConfig(cfg) {
    fs.writeFileSync(CONFIG_PATH, JSON.stringify(cfg, null, 2) + '\n', 'utf-8');
}

function loadSentLog() {
    if (!fs.existsSync(LOG_PATH)) {
        return { sent: {}, invalid_wa: {} };
    }
    try {
        const parsed = JSON.parse(fs.readFileSync(LOG_PATH, 'utf-8'));
        return {
            sent: parsed.sent || {},
            invalid_wa: parsed.invalid_wa || {},
        };
    } catch {
        return { sent: {}, invalid_wa: {} };
    }
}

function saveSentLog(logData) {
    fs.writeFileSync(LOG_PATH, JSON.stringify(logData, null, 2) + '\n', 'utf-8');
}

/**
 * Load 72 entries & deduplicate by normalized WhatsApp number (628...).
 * If two rows share the exact same phone number (e.g. #31 "Muhammad Arshaka" &
 * #58 "Muhammad Arshaka Alghozali"), keep the fuller child name so the parent
 * is only messaged once.
 */
function loadDeduplicatedRecipients() {
    const rawList = JSON.parse(fs.readFileSync(DATA_PATH, 'utf-8'));
    const byPhone = new Map();
    const duplicates = [];

    for (const item of rawList) {
        const phone62 = normalizeTo62(item.phone);
        if (!phone62 || phone62.length < 10) continue;

        if (byPhone.has(phone62)) {
            const existing = byPhone.get(phone62);
            duplicates.push({
                phone: formatLocal08(phone62),
                first: `#${existing.no} ${existing.full_name}`,
                second: `#${item.no} ${item.full_name}`,
            });
            // Prefer the longer / more complete full_name
            if (String(item.full_name).trim().length > String(existing.full_name).trim().length) {
                byPhone.set(phone62, {
                    ...item,
                    phone62,
                    phone08: formatLocal08(phone62),
                    mergedFromNo: existing.no,
                });
            }
        } else {
            byPhone.set(phone62, {
                ...item,
                phone62,
                phone08: formatLocal08(phone62),
            });
        }
    }

    return {
        totalRaw: rawList.length,
        recipients: Array.from(byPhone.values()),
        duplicates,
    };
}

/**
 * Build Anti-Ban Personalized Message (Dynamic Spintax + Unique Reference Hash)
 */
function buildPersonalizedMessage(recipient, config) {
    const childName = recipient.full_name.trim();
    const prevSchool = (recipient.previous_school || '').trim();
    const isInternalTk = prevSchool.toLowerCase().includes('namira');
    const schoolName = config.schoolName || 'SD Namira Kraksaan';
    const academicYear = config.academicYear || '2027/2028';
    const groupLink = config.groupInviteLink;
    const regUrl = config.registrationUrl || 'https://namiraschool.com/spmb/daftar/sd';

    const greetings = [
        "Assalamu'alaikum Wr. Wb.",
        "Assalamu'alaikum Warahmatullahi Wabarakatuh,",
        "Assalamu'alaikum Ayah/Bunda,",
    ];

    const salutations = [
        `Yth. Bapak/Ibu Orang Tua dari ananda *${childName}*`,
        `Kepada Yth. Ayah/Bunda Wali dari ananda *${childName}*`,
        `Halo Bapak/Ibu Orang Tua ananda *${childName}*`,
    ];

    const prayers = [
        'Semoga Bapak/Ibu beserta keluarga senantiasa dalam keadaan sehat wal afiat dan penuh keberkahan.',
        'Semoga Ayah/Bunda sekeluarga selalu diberikan kesehatan dan kelancaran dalam setiap aktivitas.',
        'Semoga Bapak/Ibu sekeluarga selalu dalam lindungan Allah SWT dan sehat selalu.',
    ];

    const introParagraph = isInternalTk
        ? pickRandom([
              `Terima kasih atas kepercayaan Ayah/Bunda yang telah mencatatkan ananda *${childName}* (${prevSchool}) dalam daftar inden calon murid baru *${schoolName}* Tahun Ajaran *${academicYear}*.`,
              `Kami mengucapkan terima kasih atas kepercayaan Bapak/Ibu yang telah mendaftarkan ananda *${childName}* dari *${prevSchool}* pada program inden *${schoolName}* Tahun Ajaran *${academicYear}*.`,
          ])
        : pickRandom([
              `Terima kasih atas kepercayaan Bapak/Ibu yang telah mendaftarkan ananda *${childName}* (asal sekolah: *${prevSchool}*) pada daftar inden calon murid baru *${schoolName}* Tahun Ajaran *${academicYear}*.`,
              `Kami mengucapkan terima kasih atas kepercayaan Ayah/Bunda yang telah mencatatkan ananda *${childName}* (*${prevSchool}*) dalam daftar inden *${schoolName}* Tahun Ajaran *${academicYear}*.`,
          ]);

    const groupInviteIntros = [
        'Agar koordinasi jadwal observasi dan pengumuman resmi berjalan nyaman (tanpa memasukkan nomor secara mendadak), kami mengundang Bapak/Ibu untuk bergabung ke *Grup WhatsApp Resmi Calon Wali Murid* melalui tautan berikut:',
        'Untuk memudahkan penyampaian informasi tahapan SPMB, jadwal kegiatan, dan koordinasi bersama panitia, silakan Ayah/Bunda bergabung ke *Grup WhatsApp Resmi Orang Tua Calon Murid* melalui link undangan di bawah ini:',
        'Melalui pesan ini, kami mengundang Ayah/Bunda untuk bergabung secara langsung ke *Grup WhatsApp Resmi Orang Tua Calon Murid Baru* guna mendapatkan informasi jadwal dan tahapan selanjutnya:',
    ];

    const regReminderBlocks = [
        `Selain itu, bagi Bapak/Ibu yang belum melengkapi biodata pendaftaran online di sistem SPMB, silakan mengisi melalui tautan resmi berikut (pilih kategori *Sudah Terdaftar Inden*, berkas dokumen dapat menyusul fleksibel di portal):\n${regUrl}\n_(Apabila sudah mengisi formulir online tersebut, mohon abaikan tautan pendaftaran di atas)_`,
        `Sebagai langkah pendataan sistem, bagi Ayah/Bunda yang belum mengisi formulir biodata online dapat melengkapinya melalui link berikut (pilih opsi *Sudah Terdaftar Inden*):\n${regUrl}\n_(Jika Bapak/Ibu sudah mengisi sebelumnya, tautan formulir di atas dapat diabaikan)_`,
    ];

    const closings = [
        `Terima kasih banyak atas perhatian dan kerja sama Bapak/Ibu.\nWassalamu'alaikum Wr. Wb.`,
        `Jazakumullahu khairan katsiran atas perhatian dan kerja sama Ayah/Bunda.\nWassalamu'alaikum Warahmatullahi Wabarakatuh.`,
        `Demikian informasi yang dapat kami sampaikan, terima kasih atas perhatian Ayah/Bunda.\nWassalamu'alaikum Wr. Wb.`,
    ];

    const refCode = `SPMB-SDN/${String(recipient.no).padStart(2, '0')}`;

    return [
        pickRandom(greetings),
        '',
        `${pickRandom(salutations)},`,
        pickRandom(prayers),
        '',
        introParagraph,
        '',
        pickRandom(groupInviteIntros),
        `👉 ${groupLink}`,
        '',
        pickRandom(regReminderBlocks),
        '',
        pickRandom(closings),
        '',
        `*${config.contactPerson || 'Panitia SPMB SD Namira Kraksaan'}*`,
        `_Kode Inden: ${refCode}_`,
    ].join('\n');
}

async function countdownTimer(seconds, label) {
    for (let remaining = seconds; remaining > 0; remaining--) {
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        const timeStr = mins > 0 ? `${mins}m ${String(secs).padStart(2, '0')}d` : `${secs} detik`;
        process.stdout.write(`\r⏳ ${label}: menunggu ${timeStr}...   `);
        await sleep(1000);
    }
    process.stdout.write('\r' + ' '.repeat(75) + '\r');
}

async function ensureInteractiveConfig(config, requireTestPhone = false) {
    const rl = readline.createInterface({ input, output });
    let updated = false;

    try {
        const link = String(config.groupInviteLink || '').trim();
        if (!link || link.includes('ISI_LINK_GRUP_DISINI') || !link.startsWith('https://chat.whatsapp.com/')) {
            console.log('\n============================================================');
            console.log('🔗 KONFIGURASI LINK GRUP WHATSAPP RESMI');
            console.log('============================================================');
            console.log('Link Grup WhatsApp belum diisi di config.json.');
            const answer = (await rl.question('👉 Tempel Link Grup WA (https://chat.whatsapp.com/...): ')).trim();
            if (!answer.startsWith('https://chat.whatsapp.com/')) {
                console.error('\n❌ Link tidak valid! Link undangan grup WA harus diawali https://chat.whatsapp.com/');
                process.exit(1);
            }
            config.groupInviteLink = answer;
            updated = true;
        }

        if (requireTestPhone) {
            const testPhone = String(config.testPhone || '').trim();
            if (!testPhone || normalizeTo62(testPhone).length < 10) {
                console.log('\n============================================================');
                console.log('📱 NOMOR WHATSAPP UNTUK TES PESAN CONTOH');
                console.log('============================================================');
                const answerPhone = (await rl.question('👉 Masukkan Nomor WA Anda sendiri untuk tes 1 pesan (contoh: 082332922521): ')).trim();
                if (normalizeTo62(answerPhone).length < 10) {
                    console.error('\n❌ Nomor WhatsApp tidak valid!');
                    process.exit(1);
                }
                config.testPhone = answerPhone;
                updated = true;
            }
        }

        if (updated) {
            saveConfig(config);
            console.log('✅ Konfigurasi berhasil disimpan otomatis ke config.json!\n');
        }
    } finally {
        rl.close();
    }
}

// ============================================================================
// Utility Modes (--logout, --reset-log, --status, --dry-run)
// ============================================================================

if (isLogout) {
    if (fs.existsSync(AUTH_DIR)) {
        fs.rmSync(AUTH_DIR, { recursive: true, force: true });
        console.log('✅ Sesi login WhatsApp (auth_session) berhasil dihapus. Anda dapat scan QR nomor baru.');
    } else {
        console.log('ℹ️ Belum ada sesi login WhatsApp yang tersimpan.');
    }
    process.exit(0);
}

if (isResetLog) {
    if (fs.existsSync(LOG_PATH)) {
        const backupPath = LOG_PATH.replace('.json', `_backup_${Date.now()}.json`);
        fs.renameSync(LOG_PATH, backupPath);
        console.log(`✅ Riwayat pengiriman berhasil di-reset (backup disimpan di ${path.basename(backupPath)}).`);
    } else {
        console.log('ℹ️ Belum ada file sent_log.json.');
    }
    process.exit(0);
}

const config = loadConfig();
const { totalRaw, recipients, duplicates } = loadDeduplicatedRecipients();
const sentLog = loadSentLog();

if (isStatusMode) {
    const sentCount = recipients.filter((r) => Boolean(sentLog.sent[r.phone62])).length;
    const invalidCount = recipients.filter((r) => Boolean(sentLog.invalid_wa[r.phone62])).length;
    const pendingCount = recipients.length - sentCount - invalidCount;

    console.log('\n============================================================');
    console.log('📊 STATUS PENGIRIMAN BOT WA INDEN SD NAMIRA KRAKSAAN');
    console.log('============================================================');
    console.log(`• Total Baris Excel Inden     : ${totalRaw} baris`);
    console.log(`• Nomor Duplikat Digabung     : ${duplicates.length} nomor (${duplicates.map((d) => `${d.phone}: ${d.first} & ${d.second}`).join('; ') || '-'})`);
    console.log(`• Total Nomor WA Unik         : ${recipients.length} nomor`);
    console.log(`• Sudah Terkirim              : ${sentCount} nomor`);
    console.log(`• Nomor Tidak Aktif di WA     : ${invalidCount} nomor`);
    console.log(`• Sisa Belum Dikirim          : ${pendingCount} nomor`);
    console.log(`• Link Grup WA Saat Ini       : ${config.groupInviteLink}`);
    console.log('============================================================\n');
    process.exit(0);
}

if (isExportSisa) {
    const pendingList = recipients.filter(
        (r) => !sentLog.sent[r.phone62] && !sentLog.invalid_wa[r.phone62]
    );
    const htmlPath = path.join(__dirname, 'SISA_BELUM_TERKIRIM.html');
    const rowsHtml = pendingList
        .map((item, idx) => {
            const msg = buildPersonalizedMessage(item, config);
            const waUrl = `https://wa.me/${item.phone62}?text=${encodeURIComponent(msg)}`;
            return `
            <tr id="row-${item.no}" class="item-row">
                <td class="num">${idx + 1} <span class="sub">(Excel #${item.no})</span></td>
                <td>
                    <strong>${item.full_name}</strong>
                    <div class="sub">${item.previous_school} &bull; ${item.source_label}</div>
                </td>
                <td class="mono">${item.phone08}</td>
                <td class="actions">
                    <a href="${waUrl}" target="_blank" rel="noopener noreferrer" class="btn-wa" onclick="markDone(${item.no})">
                        Kirim WA Manual (1-Klik)
                    </a>
                    <label class="chk">
                        <input type="checkbox" id="chk-${item.no}" onchange="toggleDone(${item.no}, this.checked)"> Selesai
                    </label>
                </td>
            </tr>`;
        })
        .join('\n');

    const htmlContent = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sisa ${pendingList.length} Nomor Inden Belum Terkirim - SD Namira Kraksaan</title>
<style>
  body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 24px; }
  .wrap { max-width: 960px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
  h1 { margin: 0 0 6px; font-size: 20px; color: #064e3b; }
  p { margin: 0 0 16px; font-size: 13px; color: #475569; line-height: 1.5; }
  .alert { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 12px; font-size: 13px; margin-bottom: 18px; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { text-align: left; background: #f1f5f9; padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
  td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  tr.done { background: #f0fdf4; opacity: 0.65; }
  .sub { font-size: 11px; color: #64748b; margin-top: 2px; }
  .mono { font-family: ui-monospace, monospace; font-weight: 700; }
  .actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .btn-wa { display: inline-block; background: #16a34a; color: #fff; text-decoration: none; font-weight: 700; font-size: 12px; padding: 8px 14px; border-radius: 10px; }
  .btn-wa:hover { background: #15803d; }
  .chk { font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
</style>
</head>
<body>
  <div class="wrap">
    <h1>Daftar Sisa ${pendingList.length} Nomor Inden SD Namira Kraksaan (Belum Terkirim)</h1>
    <p>54 dari 71 nomor unik sudah berhasil terkirim otomatis oleh bot. Untuk <strong>${pendingList.length} nomor sisa</strong> di bawah ini, Anda dapat mengirimkannya secara manual 1-klik lewat WhatsApp Web / HP Panitia lain (beri jeda santai 1–2 menit per klik agar 100% aman).</p>
    <div class="alert">
      <strong>Tips Aman:</strong> File ini bisa dibuka di Laptop (WhatsApp Web nomor panitia ke-2) atau dikirim ke HP Panitia lain lalu dibuka di Chrome HP. Centang otomatis tersimpan saat tombol diklik.
    </div>
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Calon Murid & Asal Sekolah</th>
          <th>No. WhatsApp</th>
          <th>Aksi Manual 1-Klik</th>
        </tr>
      </thead>
      <tbody>
        ${rowsHtml}
      </tbody>
    </table>
  </div>
<script>
  const KEY = 'spmb_sdn_sisa_done_v1';
  const saved = JSON.parse(localStorage.getItem(KEY) || '{}');
  function applyState() {
    Object.keys(saved).forEach(no => {
      const tr = document.getElementById('row-' + no);
      const chk = document.getElementById('chk-' + no);
      if (tr && chk) {
        chk.checked = !!saved[no];
        tr.classList.toggle('done', !!saved[no]);
      }
    });
  }
  function toggleDone(no, val) {
    saved[no] = val;
    localStorage.setItem(KEY, JSON.stringify(saved));
    applyState();
  }
  function markDone(no) {
    toggleDone(no, true);
  }
  applyState();
</script>
</body>
</html>`;

    fs.writeFileSync(htmlPath, htmlContent, 'utf-8');
    console.log(`✅ File daftar sisa (${pendingList.length} nomor) berhasil dibuat: ${htmlPath}`);
    process.exit(0);
}

if (isDryRun) {
    console.log('\n============================================================');
    console.log('🧪 SIMULASI DRY-RUN (TANPA MENGIRIM PESAN KE WHATSAPP)');
    console.log('============================================================');
    console.log(`Total Baris Data : ${totalRaw} -> ${recipients.length} Nomor WA Unik`);
    if (duplicates.length > 0) {
        for (const dup of duplicates) {
            console.log(`ℹ️ Deduplikasi Otomatis: Nomor ${dup.phone} muncul pada ${dup.first} & ${dup.second} (hanya dikirim 1x).`);
        }
    }
    console.log('\n--- CONTOH VARIASI PESAN #1 (Alumni TK Namira) ---\n');
    console.log(buildPersonalizedMessage(recipients[0], config));
    console.log('\n--- CONTOH VARIASI PESAN #2 (TK Luar / Umum) ---\n');
    const externalSample = recipients.find((r) => !r.previous_school.toLowerCase().includes('namira')) || recipients[1];
    console.log(buildPersonalizedMessage(externalSample, config));
    console.log('\n============================================================\n');
    process.exit(0);
}

// ============================================================================
// Live WhatsApp Mode (--test or full anti-ban queue)
// ============================================================================

await ensureInteractiveConfig(config, isTestMode);

let currentSock = null;
let isSocketOpen = false;
let isQueueStarted = false;
let openWaiters = [];

function notifySocketOpen(sock) {
    isSocketOpen = true;
    const waiters = openWaiters;
    openWaiters = [];
    for (const resolve of waiters) {
        resolve(sock);
    }
}

function waitForOpenSocket() {
    if (isSocketOpen && currentSock) {
        return Promise.resolve(currentSock);
    }
    return new Promise((resolve) => {
        openWaiters.push(resolve);
    });
}

async function sendHumanizedWhatsAppMessage(phone62, text) {
    const sock = await waitForOpenSocket();
    const jid = `${phone62}@s.whatsapp.net`;

    // 1. Check if number is registered on WhatsApp
    const [checkResult] = await sock.onWhatsApp(jid);
    if (!checkResult || !checkResult.exists) {
        return { ok: false, reason: 'Nomor tidak terdaftar di WhatsApp' };
    }

    const targetJid = checkResult.jid || jid;

    // 2. Simulate human opening chat & typing for 3.5 - 7 seconds
    try {
        await sock.presenceSubscribe(targetJid);
        await sock.sendPresenceUpdate('available', targetJid);
        await sleep(randomInt(900, 1600));
        await sock.sendPresenceUpdate('composing', targetJid);
        await sleep(randomInt(3500, 6800));
    } catch {
        // Presence update failure is non-fatal; continue sending
    }

    // 3. Send message
    await sock.sendMessage(targetJid, { text });

    try {
        await sock.sendPresenceUpdate('paused', targetJid);
    } catch {
        // Ignore non-fatal presence pause error
    }

    return { ok: true, jid: targetJid };
}

async function runQueueOnce() {
    if (isQueueStarted) return;
    isQueueStarted = true;

    try {
        // Mode 1: Test 1 message to Admin's own phone
        if (isTestMode) {
            const testPhone62 = normalizeTo62(config.testPhone);
            const sampleRecipient = recipients[0];
            const previewText = buildPersonalizedMessage(sampleRecipient, config);

            console.log('\n============================================================');
            console.log(`🧪 MENGIRIM 1 PESAN TES KE NOMOR ANDA (${formatLocal08(testPhone62)})`);
            console.log('============================================================');

            const res = await sendHumanizedWhatsAppMessage(testPhone62, previewText);
            if (res.ok) {
                console.log(`✅ Pesan contoh berhasil terkirim ke ${formatLocal08(testPhone62)}!`);
                console.log('👉 Silakan cek WhatsApp di HP Anda. Jika format pesan & link grup sudah sesuai, jalankan perintah utama: npm start\n');
            } else {
                console.error(`❌ Gagal mengirim ke ${formatLocal08(testPhone62)}: ${res.reason}\n`);
            }
            await sleep(2000);
            process.exit(0);
        }

        // Mode 2: Full Anti-Ban Queue for 72 Inden Data
        const pendingList = recipients.filter(
            (r) => !sentLog.sent[r.phone62] && !sentLog.invalid_wa[r.phone62]
        );

        console.log('\n============================================================');
        console.log('🚀 BOT WHATSAPP ANTI-BAN UNDANGAN GRUP INDEN SD NAMIRA');
        console.log('============================================================');
        console.log(`• Total Data Excel        : ${totalRaw} baris (${recipients.length} nomor WA unik)`);
        if (duplicates.length > 0) {
            console.log(`• Deduplikasi Nomor Sama  : ${duplicates.length} nomor digabung otomatis agar tidak terkirim dobel`);
        }
        console.log(`• Sudah Pernah Terkirim   : ${Object.keys(sentLog.sent).length} nomor`);
        console.log(`• Antrean Akan Dikirim    : ${pendingList.length} nomor`);
        console.log(`• Jeda Acak Antar Pesan   : ${config.minDelaySeconds} - ${config.maxDelaySeconds} detik`);
        console.log(`• Istirahat Tiap ${config.batchBreakEvery} Pesan : ${config.batchBreakMinMinutes} - ${config.batchBreakMaxMinutes} menit`);
        console.log('💡 Tips: Anda bisa menekan Ctrl+C kapan saja untuk menjeda. Saat dijalankan lagi, bot otomatis lanjut dari nomor berikutnya.');
        console.log('============================================================\n');

        if (pendingList.length === 0) {
            console.log('🎉 Seluruh nomor dalam daftar Inden SD Namira Kraksaan sudah selesai dikirimi undangan!');
            process.exit(0);
        }

        const maxPerRun = Number(config.maxMessagesPerRun || 75);
        const targetBatch = pendingList.slice(0, maxPerRun);
        let sentThisSession = 0;

        for (let i = 0; i < targetBatch.length; i++) {
            const item = targetBatch[i];
            const progressLabel = `[${i + 1}/${targetBatch.length}]`;
            const personalizedText = buildPersonalizedMessage(item, config);

            console.log(`${progressLabel} Mengirim ke Orang Tua *${item.full_name}* (${item.phone08} - ${item.previous_school})...`);

            try {
                const result = await sendHumanizedWhatsAppMessage(item.phone62, personalizedText);

                if (!result.ok) {
                    console.log(`   ⚠️ Dilewati: ${item.phone08} tidak terdaftar/aktif di WhatsApp.`);
                    sentLog.invalid_wa[item.phone62] = {
                        no: item.no,
                        full_name: item.full_name,
                        phone: item.phone08,
                        checked_at: new Date().toISOString(),
                        reason: result.reason,
                    };
                    saveSentLog(sentLog);
                    await sleep(3000);
                    continue;
                }

                sentThisSession++;
                sentLog.sent[item.phone62] = {
                    no: item.no,
                    full_name: item.full_name,
                    phone: item.phone08,
                    previous_school: item.previous_school,
                    sent_at: new Date().toISOString(),
                };
                saveSentLog(sentLog);

                console.log(`   ✅ Terkirim aman! (Total tersimpan: ${Object.keys(sentLog.sent).length}/${recipients.length})`);

                // Check if more items remain in this run
                const hasMore = i < targetBatch.length - 1;
                if (hasMore) {
                    if (sentThisSession > 0 && sentThisSession % Number(config.batchBreakEvery || 15) === 0) {
                        const breakSeconds = randomInt(
                            Number(config.batchBreakMinMinutes || 3) * 60,
                            Number(config.batchBreakMaxMinutes || 5) * 60
                        );
                        console.log(`\n☕ Sudah mengirim ${sentThisSession} pesan berturut-turut. Istirahat aman anti-ban dulu...`);
                        await countdownTimer(breakSeconds, 'Istirahat Batch Anti-Ban');
                    } else {
                        const delaySec = randomInt(
                            Number(config.minDelaySeconds || 45),
                            Number(config.maxDelaySeconds || 85)
                        );
                        await countdownTimer(delaySec, 'Jeda Aman Anti-Ban');
                    }
                }
            } catch (err) {
                console.error(`   ❌ Gagal sementara pada ${item.phone08}: ${err?.message || err}`);
                console.log('   🔄 Menunggu 20 detik sebelum melanjutkan ke nomor berikutnya...');
                await sleep(20000);
            }
        }

        console.log('\n============================================================');
        console.log('🎉 PENGIRIMAN SELESAI!');
        console.log(`• Berhasil dikirim sesi ini : ${sentThisSession} nomor`);
        console.log(`• Total keseluruhan selesai : ${Object.keys(sentLog.sent).length} / ${recipients.length} nomor unik`);
        console.log(`• Nomor tidak aktif WA      : ${Object.keys(sentLog.invalid_wa).length} nomor`);
        console.log('============================================================\n');
        await sleep(2000);
        process.exit(0);
    } catch (fatalErr) {
        console.error('\n❌ Terjadi kesalahan fatal:', fatalErr);
        process.exit(1);
    }
}

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);

    let version = [2, 3000, 1019707846];
    try {
        const latest = await fetchLatestBaileysVersion();
        if (latest?.version) {
            version = latest.version;
        }
    } catch {
        // Use fallback WA Web version if offline check fails
    }

    const sock = makeWASocket({
        version,
        auth: state,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        browser: Browsers.windows('Desktop'),
        syncFullHistory: false,
        markOnlineOnConnect: false,
    });

    currentSock = sock;
    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('\n============================================================');
            console.log('📱 SCAN QR CODE WHATSAPP DI BAWAH INI');
            console.log('   Buka WhatsApp di HP -> Perangkat Tertaut -> Tautkan Perangkat');
            console.log('============================================================\n');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'open') {
            console.log('\n✅ Terhubung ke WhatsApp dengan aman!');
            notifySocketOpen(sock);
            runQueueOnce();
        }

        if (connection === 'close') {
            isSocketOpen = false;
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            if (statusCode === DisconnectReason.loggedOut) {
                console.error('\n❌ Sesi WhatsApp telah logout dari HP. Jalankan "npm run logout" lalu scan ulang QR.');
                process.exit(1);
            } else if (shouldReconnect) {
                console.log('🔄 Menyambungkan ulang koneksi WhatsApp...');
                setTimeout(() => {
                    connectToWhatsApp();
                }, 2500);
            }
        }
    });
}

console.log('🔌 Menyiapkan koneksi WhatsApp Anti-Ban SD Namira Kraksaan...');
connectToWhatsApp();
