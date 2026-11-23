export const SMP_FEE_CONSTANTS = {
    PSIKOTES: 250000,
    SERAGAM_PUTRA: 1525000,
    SERAGAM_PUTRI: 1830000,
    RUTIN_BULANAN: 650000,
    INFAQ_KEGIATAN: 2000000,
    PENGEMBANGAN_INDEN: 6000000,
    PENGEMBANGAN_WAITING_LIST: 7000000,
    ALUMNI_SD_DISCOUNT: 1000000,
    ADIK_KANDUNG_DISCOUNT: 500000,
    MAHAD_TOTAL: 1650000,
};

function shortAcademicYear(applicant, setting) {
    const raw = applicant?.academic_year || setting?.academic_year || '2027/2028';
    const match = String(raw).match(/(\d{4})\/(\d{4})/);
    if (match) {
        return `${match[1].slice(-2)}/${match[2].slice(-2)}`;
    }
    return raw;
}

function startAcademicYear(applicant, setting) {
    const raw = applicant?.academic_year || setting?.academic_year || '2027/2028';
    const match = String(raw).match(/(\d{4})\/(\d{4})/);
    return match ? match[1] : '2027';
}

export function buildSmpBreakdownItems(applicant, setting, opts = {}) {
    const isFemale = String(applicant?.gender || '').toUpperCase() === 'P';
    const isInden = opts.asInden !== undefined ? Boolean(opts.asInden) : Boolean(applicant?.is_inden);
    const withAlumniDiscount = opts.withAlumniDiscount !== undefined
        ? Boolean(opts.withAlumniDiscount)
        : ['internal_sd', 'internal'].includes(applicant?.category);
    const withSiblingDiscount = Boolean(opts.withSiblingDiscount);
    const withMahad = Boolean(opts.withMahad);

    let pengembangan = isInden
        ? SMP_FEE_CONSTANTS.PENGEMBANGAN_INDEN
        : SMP_FEE_CONSTANTS.PENGEMBANGAN_WAITING_LIST;

    const notes = [isInden ? 'INDEN' : 'WAITING LIST'];
    if (withAlumniDiscount) {
        pengembangan = Math.max(0, pengembangan - SMP_FEE_CONSTANTS.ALUMNI_SD_DISCOUNT);
        notes.push('POT. ALUMNI SD');
    }
    if (withSiblingDiscount) {
        pengembangan = Math.max(0, pengembangan - SMP_FEE_CONSTANTS.ADIK_KANDUNG_DISCOUNT);
        notes.push('POT. ADIK KANDUNG');
    }

    return [
        {
            key: 'psikotes',
            label: 'PSIKOTES & OBSERVASI',
            sublabel: 'SELEKSI MASUK',
            amount: SMP_FEE_CONSTANTS.PSIKOTES,
        },
        {
            key: 'seragam',
            label: 'SERAGAM & ATRIBUT',
            sublabel: isFemale ? 'PEREMPUAN' : 'LAKI-LAKI',
            amount: isFemale ? SMP_FEE_CONSTANTS.SERAGAM_PUTRI : SMP_FEE_CONSTANTS.SERAGAM_PUTRA,
        },
        {
            key: 'rutin_bulanan',
            label: 'RUTIN BULANAN',
            sublabel: 'SPP, MAKAN, TAB. & KOMITE',
            amount: SMP_FEE_CONSTANTS.RUTIN_BULANAN,
        },
        {
            key: 'infaq_kegiatan',
            label: 'INFAQ KEGIATAN',
            sublabel: `AWAL TA ${shortAcademicYear(applicant, setting)}`,
            amount: SMP_FEE_CONSTANTS.INFAQ_KEGIATAN,
        },
        {
            key: 'infaq_pengembangan',
            label: 'INFAQ PENGEMBANGAN',
            sublabel: notes.join(' • '),
            amount: pengembangan,
        },
        {
            key: 'buku_paket',
            label: 'BUKU PAKET PENDAMPING',
            sublabel: `DIINFORMASIKAN JUNI ${startAcademicYear(applicant, setting)}`,
            amount: 0,
        },
        {
            key: 'mahad',
            label: "INFAQ MA'HAD (ASRAMA)",
            sublabel: withMahad ? 'ASRAMA PUTRA/PUTRI' : '-',
            amount: withMahad ? SMP_FEE_CONSTANTS.MAHAD_TOTAL : 0,
        },
    ];
}

export function resolveInitialSmpBreakdownState(applicant, setting) {
    const saved = applicant?.fee_breakdown;
    if (saved && Array.isArray(saved.items) && saved.items.length > 0) {
        return {
            withAlumniDiscount: Boolean(saved.with_alumni_discount),
            withSiblingDiscount: Boolean(saved.with_sibling_discount),
            withMahad: Boolean(saved.with_mahad),
            treasurerName: saved.treasurer_name || 'Nadia Pramadianti, S.M.',
            items: saved.items.map((item) => ({
                key: item.key || 'custom',
                label: item.label || '-',
                sublabel: item.sublabel || '-',
                amount: Number(item.amount || 0),
            })),
        };
    }

    const withAlumniDiscount = ['internal_sd', 'internal'].includes(applicant?.category);
    const rawFee = applicant?.total_admission_fee;
    const isFree = rawFee !== null && rawFee !== undefined && rawFee !== '' && Number(rawFee) <= 0;

    if (isFree) {
        const zeroItems = buildSmpBreakdownItems(applicant, setting, { withAlumniDiscount }).map((item) => ({
            ...item,
            amount: 0,
            sublabel: item.key === 'infaq_pengembangan' ? 'TANGGUNGAN FULL / BEBAS BIAYA' : '-',
        }));
        return {
            withAlumniDiscount,
            withSiblingDiscount: false,
            withMahad: false,
            treasurerName: 'Nadia Pramadianti, S.M.',
            items: zeroItems,
        };
    }

    return {
        withAlumniDiscount,
        withSiblingDiscount: false,
        withMahad: false,
        treasurerName: 'Nadia Pramadianti, S.M.',
        items: buildSmpBreakdownItems(applicant, setting, { withAlumniDiscount }),
    };
}

export function sumBreakdownItems(items) {
    return (items || []).reduce((acc, item) => acc + Math.max(0, Number(item?.amount || 0)), 0);
}
