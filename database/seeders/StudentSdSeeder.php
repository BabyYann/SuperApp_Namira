<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentSdSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'SD')
            ->orWhere('name', 'LIKE', '%SD%')
            ->first();
        if (!$unit) {
            echo "Unit SD Namira tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'siswa')->orWhere('name', 'student')->first();
        $roleId = $role ? $role->id : null;

        $studentsData = [
  {
    "nama": "A. RAYYAN REYNDRA ALFAREZQI",
    "nis": "0418",
    "nisn": "3182315991",
    "email": "a.rayyan.reyndra.alfarezqi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ABDUL ALIM",
    "nis": "5251497",
    "nisn": "3175905794",
    "email": "abdul.alim@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ABDULLAH FATIHUDDIN FARRAS",
    "nis": "0413",
    "nisn": "0136850115",
    "email": "abdullah.fatihuddin.farras@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Abdullah Ghani Ramadhan",
    "nis": "5261609",
    "nisn": "3197008256",
    "email": "abdullah.ghani.ramadhan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ABHI ALFATHIR",
    "nis": "5251498",
    "nisn": "3181341627",
    "email": "abhi.alfathir@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ABIMANA GIBRAN KHALFANI",
    "nis": "0240",
    "nisn": "0148764150",
    "email": "abimana.gibran.khalfani@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ABRAHAM IRSYAD ATHARRAYHAN",
    "nis": "5251499",
    "nisn": "3188763928",
    "email": "abraham.irsyad.atharrayhan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ABRIZAM ARSYAD ATHARRAYHAN",
    "nis": "5261588",
    "nisn": "3201587742",
    "email": "abrizam.arsyad.atharrayhan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ACHMAD AL GHAZALI TSAQIF RABBANI",
    "nis": "0360",
    "nisn": "3161104382",
    "email": "achmad.al.ghazali.tsaqif.rabbani@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ACHMAD ALIF FAHRIZ ZIDAN",
    "nis": "0422",
    "nisn": "3178445982",
    "email": "achmad.alif.fahriz.zidan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ADAM DILAN ALFARIZQY",
    "nis": "0419",
    "nisn": "3187778356",
    "email": "adam.dilan.alfarizqy@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ADARA KANISAFIQA NUR RAHMAT",
    "nis": "0242",
    "nisn": "0156636568",
    "email": "adara.kanisafiqa.nur.rahmat@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ADEEVA AFSHEEN MYESHA SUSANTO",
    "nis": "0361",
    "nisn": "3168182811",
    "email": "adeeva.afsheen.myesha.susanto@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Adenaya zerina yudiesthira",
    "nis": "0243",
    "nisn": "3144967430",
    "email": "adenaya.zerina.yudiesthira@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ADIBA AYUNDA",
    "nis": "0420",
    "nisn": "3174139430",
    "email": "adiba.ayunda@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ADIBA SHAKILA ATMARINI HAMDAN",
    "nis": "0421",
    "nisn": "3176671233",
    "email": "adiba.shakila.atmarini.hamdan@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ADIBAH SHAKILA ATMARINI",
    "nis": "0300",
    "nisn": "0156908462",
    "email": "adibah.shakila.atmarini@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ADINDA ANDHARA PRAMESWARI",
    "nis": "5261589",
    "nisn": "3196752927",
    "email": "adinda.andhara.prameswari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Aditiya Zarkan Ramadhan",
    "nis": "0572",
    "nisn": "3160993022",
    "email": "aditiya.zarkan.ramadhan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ADZKIYA KHADIJAH",
    "nis": "5251500",
    "nisn": "3195423235",
    "email": "adzkiya.khadijah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AFIFUDDIN HASSAN",
    "nis": "5261566",
    "nisn": "3191793695",
    "email": "afifuddin.hassan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD ALZAM ATHOILLAH",
    "nis": "0362",
    "nisn": "3160922178",
    "email": "ahmad.alzam.athoillah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD ARSYIL UMAM F",
    "nis": "0301",
    "nisn": "0158156365",
    "email": "ahmad.arsyil.umam.f@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Ahmad Azkal Anam",
    "nis": "0302",
    "nisn": "0154034463",
    "email": "ahmad.azkal.anam@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD DAHLAN ZAINAL ABIDIN",
    "nis": "0423",
    "nisn": "0172759705",
    "email": "ahmad.dahlan.zainal.abidin@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD IMDAD KHOIRUL AZMI",
    "nis": "5251501",
    "nisn": "3185888292",
    "email": "ahmad.imdad.khoirul.azmi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD MAULANA SIDDIQ",
    "nis": "0363",
    "nisn": "0169735126",
    "email": "ahmad.maulana.siddiq@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD NAZRIL RAIHAN",
    "nis": "5251502",
    "nisn": "3184880620",
    "email": "ahmad.nazril.raihan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Ahmad Qodama Raziq Aryasatya",
    "nis": "0244",
    "nisn": "0144011810",
    "email": "ahmad.qodama.raziq.aryasatya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMAD RAYYANDRA IBRAHIM AL FATTAH",
    "nis": "0424",
    "nisn": "3170596927",
    "email": "ahmad.rayyandra.ibrahim.al.fattah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHMED NAZRIL HARIYANTO",
    "nis": "0304",
    "nisn": "3151476044",
    "email": "ahmed.nazril.hariyanto@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AHYAR KAFEEL AHMAD",
    "nis": "0425",
    "nisn": "3183014639",
    "email": "ahyar.kafeel.ahmad@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AIRA SALSABILA PUTRI IRWANSYAH",
    "nis": "0426",
    "nisn": "3176337027",
    "email": "aira.salsabila.putri.irwansyah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AISHA ADIFA ASHALINA",
    "nis": "0427",
    "nisn": "3175280714",
    "email": "aisha.adifa.ashalina@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AISYAH ALMAHYRA PRASETYO",
    "nis": "5261590",
    "nisn": "3200098890",
    "email": "aisyah.almahyra.prasetyo@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AISYAH KAMILIA",
    "nis": "0428",
    "nisn": "3180788797",
    "email": "aisyah.kamilia@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AISYAH ZAINI",
    "nis": "0357",
    "nisn": "3159031757",
    "email": "aisyah.zaini@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AIZA NASIHA KAISA",
    "nis": "5251503",
    "nisn": "3198212540",
    "email": "aiza.nasiha.kaisa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AIZZAH NAFILATUL MUKHBITA",
    "nis": "0429",
    "nisn": "3178201793",
    "email": "aizzah.nafilatul.mukhbita@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AKIF ALLI ZHAFIR",
    "nis": "0365",
    "nisn": "3172855543",
    "email": "akif.alli.zhafir@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALBYAZKA ALHANAN EFFENDI",
    "nis": "0430",
    "nisn": "3180319149",
    "email": "albyazka.alhanan.effendi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALEA SHAFIYAH",
    "nis": "0245",
    "nisn": "0142423947",
    "email": "alea.shafiyah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALEXANDRIA MUMTAZAH",
    "nis": "5261591",
    "nisn": "3207896373",
    "email": "alexandria.mumtazah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALFAREZ NEBRAS LESMANA",
    "nis": "0431",
    "nisn": "3183420669",
    "email": "alfarez.nebras.lesmana@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALFARIQ FADHIL WIRAWAN",
    "nis": "0432",
    "nisn": "3181781045",
    "email": "alfariq.fadhil.wirawan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALFI MANZILATURROHMA",
    "nis": "0366",
    "nisn": "3162839294",
    "email": "alfi.manzilaturrohma@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALIF NUR RISQI PUTRA",
    "nis": "5261592",
    "nisn": "3194071220",
    "email": "alif.nur.risqi.putra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALINA MALAIKA HADIANSAH",
    "nis": "5251505",
    "nisn": "3192397091",
    "email": "alina.malaika.hadiansah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALIZA SHAFIQA WIRAWAN",
    "nis": "0246",
    "nisn": "0143459558",
    "email": "aliza.shafiqa.wirawan@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALLYSA HUMAIRA AZZAHRA",
    "nis": "5261610",
    "nisn": "3192269385",
    "email": "allysa.humaira.azzahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Almahdi Muhamad Revaldan",
    "nis": "5261567",
    "nisn": "3204093105",
    "email": "almahdi.muhamad.revaldan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Alrescha Denandra Hermawan",
    "nis": "5261593",
    "nisn": "3194516440",
    "email": "alrescha.denandra.hermawan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALRIANA D GIENKA",
    "nis": "5261611",
    "nisn": "3197750476",
    "email": "alriana.d.gienka@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALTHAF PRAMADANA HADIANSAH",
    "nis": "0305",
    "nisn": "3152415497",
    "email": "althaf.pramadana.hadiansah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALTHAFF ABQARY RAFFASYA",
    "nis": "5251506",
    "nisn": "3182655227",
    "email": "althaff.abqary.raffasya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALVIANO ABINAYA VHALEANDRA",
    "nis": "0435",
    "nisn": "3181025154",
    "email": "alviano.abinaya.vhaleandra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ALVINO RIZKIAN MUBARAK",
    "nis": "0568",
    "nisn": "3175794713",
    "email": "alvino.rizkian.mubarak@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Alya Zafira Halwah Qanita",
    "nis": "5261594",
    "nisn": "3196077527",
    "email": "alya.zafira.halwah.qanita@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ALZENA MUFIA AJIE",
    "nis": "5251507",
    "nisn": "3183375342",
    "email": "alzena.mufia.ajie@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ANASTASIYA KANYAZEVA",
    "nis": "5261568",
    "nisn": "3195642732",
    "email": "anastasiya.kanyazeva@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ANISA FAIHA ASKAL ASKIYAH",
    "nis": "0493",
    "nisn": "0142256512",
    "email": "anisa.faiha.askal.askiyah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AQIFA NAILA",
    "nis": "0308",
    "nisn": "3156145095",
    "email": "aqifa.naila@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AQILA DHATU CALLISTA",
    "nis": "0250",
    "nisn": "0158638327",
    "email": "aqila.dhatu.callista@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ARIE KINANDARI DAMAYORA",
    "nis": "0309",
    "nisn": "3152011606",
    "email": "arie.kinandari.damayora@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ARINA MANASIKANA",
    "nis": "0310",
    "nisn": "3162712401",
    "email": "arina.manasikana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ARINDA SIREGAR MUTIA SUGIARTO",
    "nis": "5261626",
    "nisn": "3192038776",
    "email": "arinda.siregar.mutia.sugiarto@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ARSAKHA RANSI ALDEN L SATRIYO",
    "nis": "0312",
    "nisn": "0153392637",
    "email": "arsakha.ransi.alden.l.satriyo@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ARSENIO GIBRAN TSABAT KURNIAWAN",
    "nis": "5251508",
    "nisn": "3184727253",
    "email": "arsenio.gibran.tsabat.kurniawan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ARSYA KEYZARO FEBRIAZAR HERMANSYAH",
    "nis": "0367",
    "nisn": "3171284815",
    "email": "arsya.keyzaro.febriazar.hermansyah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ARSYIFA SALSABILLA",
    "nis": "0251",
    "nisn": "0157862466",
    "email": "arsyifa.salsabilla@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ARSYILA JINGGA ASKARA",
    "nis": "0368",
    "nisn": "3178176842",
    "email": "arsyila.jingga.askara@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ARYANNE TITANIA RINALDY",
    "nis": "0436",
    "nisn": "3174681876",
    "email": "aryanne.titania.rinaldy@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ASHRAF EMRAN ALFATIH",
    "nis": "5251509",
    "nisn": "3184154351",
    "email": "ashraf.emran.alfatih@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ASSYIFA NURFADILAH MAULANA",
    "nis": "0369",
    "nisn": "3170291205",
    "email": "assyifa.nurfadilah.maulana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ATHA ABDULLAH",
    "nis": "5261612",
    "nisn": "3190448316",
    "email": "atha.abdullah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ATHALIA MAHESHWARI FARZANA",
    "nis": "5261595",
    "nisn": "3198632715",
    "email": "athalia.maheshwari.farzana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ATHAYA ADHYASTA NABHAN PRANAJA ABI KHOIR",
    "nis": "0313",
    "nisn": "0166692633",
    "email": "athaya.adhyasta.nabhan.pranaja.abi.khoir@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Athazaky Hiro Rifiansyah",
    "nis": "5251510",
    "nisn": "3181836134",
    "email": "athazaky.hiro.rifiansyah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ATMADEVA SATRIA AZREEL",
    "nis": "0437",
    "nisn": "0176668327",
    "email": "atmadeva.satria.azreel@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ATTAYA SABASTA YUSUF",
    "nis": "5251511",
    "nisn": "3193386723",
    "email": "attaya.sabasta.yusuf@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AUFARELLIO RAMADANISH",
    "nis": "0252",
    "nisn": "0149869384",
    "email": "aufarellio.ramadanish@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AULIA NADHIFA",
    "nis": "0370",
    "nisn": "0178928180",
    "email": "aulia.nadhifa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AULIAN FARA GHAIDA UNTARI",
    "nis": "0438",
    "nisn": "3178991722",
    "email": "aulian.fara.ghaida.untari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AURIN NAHYA FI'ANATILLAH",
    "nis": "0314",
    "nisn": "3151673712",
    "email": "aurin.nahya.fi.anatillah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AYUNINDYA BINAR INARA",
    "nis": "5251512",
    "nisn": "3189920642",
    "email": "ayunindya.binar.inara@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AZKA ARZAQUNA PUTRA ADITYA",
    "nis": "5251513",
    "nisn": "3180336141",
    "email": "azka.arzaquna.putra.aditya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AZKA DANIYAL AYYUBI",
    "nis": "5251514",
    "nisn": "0158136889",
    "email": "azka.daniyal.ayyubi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AZKA PRATAMA ABDULLAH",
    "nis": "0315",
    "nisn": "0159637136",
    "email": "azka.pratama.abdullah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Azmi Muhammad Alkayyis",
    "nis": "5251516",
    "nisn": "3183877909",
    "email": "azmi.muhammad.alkayyis@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AZMYA KHADIJAH YUMNAIRA",
    "nis": "0439",
    "nisn": "3178263886",
    "email": "azmya.khadijah.yumnaira@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AZQIARA MAHYA TJIPTA YONICO",
    "nis": "0371",
    "nisn": "3178753152",
    "email": "azqiara.mahya.tjipta.yonico@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AZRIL SHALAHUDDIN AL-AYYUBI",
    "nis": "0489",
    "nisn": "3167937416",
    "email": "azril.shalahuddin.al.ayyubi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "AZZAHRA SAPHIERE ALFATHUNNISA",
    "nis": "0440",
    "nisn": "3175015322",
    "email": "azzahra.saphiere.alfathunnisa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "AZZAM HAIDAR PRATAMA",
    "nis": "0253",
    "nisn": "0145661709",
    "email": "azzam.haidar.pratama@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "BAHJAH AL-AQLA AL-FAYYADL",
    "nis": "0441",
    "nisn": "3173848345",
    "email": "bahjah.al.aqla.al.fayyadl@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "BAYADL MUHAMMAD AL FAYYADL",
    "nis": "0372",
    "nisn": "0164144652",
    "email": "bayadl.muhammad.al.fayyadl@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "BINTANG ARSHAKA VIRENDRA",
    "nis": "0316",
    "nisn": "0155314405",
    "email": "bintang.arshaka.virendra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "BINTANG MERRY ANGEL PRAMESWARI",
    "nis": "5261596",
    "nisn": "3191614660",
    "email": "bintang.merry.angel.prameswari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "BIRAMA BUMI RANUMERU",
    "nis": "5251517",
    "nisn": "3184356524",
    "email": "birama.bumi.ranumeru@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "BISMA HAMADALLAH",
    "nis": "0373",
    "nisn": "0164513212",
    "email": "bisma.hamadallah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Brian Alcander Sasmita",
    "nis": "0569",
    "nisn": "3177533199",
    "email": "brian.alcander.sasmita@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "CALLYSTA MELVIN QUEENA NATHANIA PUTRI",
    "nis": "5251518",
    "nisn": "3189136229",
    "email": "callysta.melvin.queena.nathania.putri@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "CEISYAHIRA TSABITA FATTAH",
    "nis": "0317",
    "nisn": "0155458636",
    "email": "ceisyahira.tsabita.fattah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "CHRISTYA ABIMANYU ERLANGGA",
    "nis": "0318",
    "nisn": "0151274297",
    "email": "christya.abimanyu.erlangga@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "CINDY SHIDQIYA RAHMAN",
    "nis": "0319",
    "nisn": "0153736864",
    "email": "cindy.shidqiya.rahman@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DAISHA INARA LAIQANISA",
    "nis": "5261597",
    "nisn": "3191070162",
    "email": "daisha.inara.laiqanisa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DANIAH AL-GIBTHA",
    "nis": "0442",
    "nisn": "3180155274",
    "email": "daniah.al.gibtha@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Danica Belvazia Sarwono",
    "nis": "5261569",
    "nisn": "3199378926",
    "email": "danica.belvazia.sarwono@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DARRA CALLIA RAFANIA AZZAHRA",
    "nis": "0320",
    "nisn": "0165598572",
    "email": "darra.callia.rafania.azzahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DAYYINAH SEFQIA AIDA ARSYARIFA",
    "nis": "0254",
    "nisn": "0147303475",
    "email": "dayyinah.sefqia.aida.arsyarifa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DENISYA PUTERI MAZAYA",
    "nis": "0255",
    "nisn": "0155493891",
    "email": "denisya.puteri.mazaya@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DIANDRA ZULMI REYNOVAN",
    "nis": "0443",
    "nisn": "3173088724",
    "email": "diandra.zulmi.reynovan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Drucilla Afeeya Qhairina Dzafira",
    "nis": "5251519",
    "nisn": "3184430812",
    "email": "drucilla.afeeya.qhairina.dzafira@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DZAKIYAH NURIS SHOBAH",
    "nis": "5251520",
    "nisn": "3180308793",
    "email": "dzakiyah.nuris.shobah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "DZURROTUL KHITAMI RAMADHANI",
    "nis": "5261613",
    "nisn": "3205058581",
    "email": "dzurrotul.khitami.ramadhani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "EL ZAYN MUBARAK",
    "nis": "5261614",
    "nisn": "3197145244",
    "email": "el.zayn.mubarak@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ELENA NOORA MALIKA",
    "nis": "0375",
    "nisn": "3174269617",
    "email": "elena.noora.malika@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ELVAN EKA WIJAYA",
    "nis": "5251521",
    "nisn": "3189166509",
    "email": "elvan.eka.wijaya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ERINKA NADHIRA ALMAHYRA",
    "nis": "5251522",
    "nisn": "3184509276",
    "email": "erinka.nadhira.almahyra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FALISHA ALMAHYRA AZKADINA",
    "nis": "5251523",
    "nisn": "3192660474",
    "email": "falisha.almahyra.azkadina@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "faradiah liviani qulbi",
    "nis": "0321",
    "nisn": "0157048431",
    "email": "faradiah.liviani.qulbi@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FAREL UMAIR WIBOWO",
    "nis": "5261615",
    "nisn": "3192647367",
    "email": "farel.umair.wibowo@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "FAREZI FATIH WIBOWO",
    "nis": "0376",
    "nisn": "3168957121",
    "email": "farezi.fatih.wibowo@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "FARHANAH AMRINA ROSYADA",
    "nis": "0573",
    "nisn": "3173328031",
    "email": "farhanah.amrina.rosyada@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FARIZ NAUFAL GHANI AHMAD",
    "nis": "5261627",
    "nisn": "3178632179",
    "email": "fariz.naufal.ghani.ahmad@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "FATHIYYAH LASHIRA KURNIAWAN",
    "nis": "5261616",
    "nisn": "3200395660",
    "email": "fathiyyah.lashira.kurniawan@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FATICHAH KANZI PUTRI FIRDAUSI",
    "nis": "0444",
    "nisn": "3172446895",
    "email": "fatichah.kanzi.putri.firdausi@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FATIMAH AZZAHRO R. LESTARI",
    "nis": "0322",
    "nisn": "3166110271",
    "email": "fatimah.azzahro.r.lestari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FAUZIYAH S DARAINI ABDULLAH",
    "nis": "0445",
    "nisn": "3176376523",
    "email": "fauziyah.s.daraini.abdullah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FELICE JAN NOTOPURO",
    "nis": "0446",
    "nisn": "3183821670",
    "email": "felice.jan.notopuro@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FILDZAH ALIYA MUFIDA",
    "nis": "0377",
    "nisn": "3167336686",
    "email": "fildzah.aliya.mufida@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "FILLIO RASYDAN AHNAF HUZAINI",
    "nis": "0323",
    "nisn": "0159714255",
    "email": "fillio.rasydan.ahnaf.huzaini@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "GADIS CALISTA ALICIA",
    "nis": "0324",
    "nisn": "0159571235",
    "email": "gadis.calista.alicia@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "GIANLUCA ALVARO XAVIER BIL FAQIH",
    "nis": "5261570",
    "nisn": "3199417046",
    "email": "gianluca.alvaro.xavier.bil.faqih@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Gibran Faeyza Mauza",
    "nis": "0378",
    "nisn": "0161397808",
    "email": "gibran.faeyza.mauza@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "GIBRAN ILMAN KHATTAB",
    "nis": "0327",
    "nisn": "3156144012",
    "email": "gibran.ilman.khattab@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "GILANG RAMADHAN ILHAM",
    "nis": "0450",
    "nisn": "3185662253",
    "email": "gilang.ramadhan.ilham@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "GRISELDA BERLIYANI",
    "nis": "0451",
    "nisn": "3177572164",
    "email": "griselda.berliyani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "HAFIDZAH KHANZA AZKADINA",
    "nis": "0452",
    "nisn": "0178839853",
    "email": "hafidzah.khanza.azkadina@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "HAIDAR AHMAD ABRIZAM",
    "nis": "0453",
    "nisn": "3188359880",
    "email": "haidar.ahmad.abrizam@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "HAIKAL MAULANA AMINUDDIN",
    "nis": "5251526",
    "nisn": "3187991362",
    "email": "haikal.maulana.aminuddin@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "HANA AMALLYA ZAHRA",
    "nis": "5251527",
    "nisn": "3194553599",
    "email": "hana.amallya.zahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "HANIF MAULANA YUSUF",
    "nis": "5261571",
    "nisn": "3193691870",
    "email": "hanif.maulana.yusuf@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Hanin Al Fatih",
    "nis": "0353",
    "nisn": "0142398928",
    "email": "hanin.al.fatih@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "HANUF DJOYA AZZAHRA",
    "nis": "0325",
    "nisn": "0153547953",
    "email": "hanuf.djoya.azzahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "HANUM FITYAH HANIFAH",
    "nis": "0257",
    "nisn": "0157300787",
    "email": "hanum.fityah.hanifah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "HARIS ANDAR RAFFASYA",
    "nis": "0258",
    "nisn": "0141912582",
    "email": "haris.andar.raffasya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "HILMAN MAOLANA",
    "nis": "0379",
    "nisn": "3169224607",
    "email": "hilman.maolana@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "HUSEIN MUHAMMAD ASSEGAF",
    "nis": "0454",
    "nisn": "3174837275",
    "email": "husein.muhammad.assegaf@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "IBRAHIMZAYN BRILLANDO ASMARA",
    "nis": "0455",
    "nisn": "3178250243",
    "email": "ibrahimzayn.brillando.asmara@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "INSYIRA MARYAM RUMAISHA",
    "nis": "5251528",
    "nisn": "3188545520",
    "email": "insyira.maryam.rumaisha@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "JAGAD ASKARA BIRU",
    "nis": "5261598",
    "nisn": "3203815934",
    "email": "jagad.askara.biru@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "JAMILA BOUHIRED",
    "nis": "0381",
    "nisn": "3167961967",
    "email": "jamila.bouhired@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "JIHAN MAKAYLA FITRI FARZHANA",
    "nis": "5251529",
    "nisn": "3185244537",
    "email": "jihan.makayla.fitri.farzhana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "JINANI FARADIS NAQSYA HIDAYATULLAH",
    "nis": "5261572",
    "nisn": "3195652812",
    "email": "jinani.faradis.naqsya.hidayatullah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "JULIO AHNAF NUGROHO",
    "nis": "0328",
    "nisn": "0159803632",
    "email": "julio.ahnaf.nugroho@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "KAESANG AGNIBRATA PUTRA PRATAMA",
    "nis": "5261617",
    "nisn": "3195757957",
    "email": "kaesang.agnibrata.putra.pratama@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "KALANDRA HAMIZAN ALFAREZI",
    "nis": "5261573",
    "nisn": "3195670348",
    "email": "kalandra.hamizan.alfarezi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "KEINARRA SUNSHINE ALMAHYRA",
    "nis": "0382",
    "nisn": "3175755648",
    "email": "keinarra.sunshine.almahyra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KEISHA AYU RAMADHANI",
    "nis": "5251530",
    "nisn": "3181979145",
    "email": "keisha.ayu.ramadhani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KEITA DYRA YUSANDY",
    "nis": "5261574",
    "nisn": "3185721824",
    "email": "keita.dyra.yusandy@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "KHAILEENA MARZIA NAJMA RAMADHANI AR RUDAF",
    "nis": "5261599",
    "nisn": "3190500986",
    "email": "khaileena.marzia.najma.ramadhani.ar.rudaf@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KHALISATUL JANNAH",
    "nis": "5261575",
    "nisn": "3198224408",
    "email": "khalisatul.jannah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KHANDRA ALGHIFFARI ZAINULLAH PUTRA",
    "nis": "0457",
    "nisn": "3173291119",
    "email": "khandra.alghiffari.zainullah.putra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "KHAYLA ALMIRA MARITZA",
    "nis": "0259",
    "nisn": "0147071852",
    "email": "khayla.almira.maritza@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KIANDRA KYNTHIA AZIZAH SULANDRA",
    "nis": "0260",
    "nisn": "0152529928",
    "email": "kiandra.kynthia.azizah.sulandra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KINARIAN NADIRA SHANUM",
    "nis": "5261600",
    "nisn": "3203179584",
    "email": "kinarian.nadira.shanum@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "KING ALTAF KIANO YUSUF",
    "nis": "0331",
    "nisn": "0161975576",
    "email": "king.altaf.kiano.yusuf@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "KYAGUS DAFI HUSNAYAIN SUSANTO",
    "nis": "0262",
    "nisn": "0144977923",
    "email": "kyagus.dafi.husnayain.susanto@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "LABIQA ALIYATUL HIMMAH",
    "nis": "5261576",
    "nisn": "3193728881",
    "email": "labiqa.aliyatul.himmah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "LAURA MARISKA PRATAMA",
    "nis": "5251531",
    "nisn": "3186789805",
    "email": "laura.mariska.pratama@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "LINTANG ANINDYA FAHMAWATI",
    "nis": "0458",
    "nisn": "3180457144",
    "email": "lintang.anindya.fahmawati@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Lizam Maulidani Ibrohim",
    "nis": "0459",
    "nisn": "3178257355",
    "email": "lizam.maulidani.ibrohim@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "LUBNA MIRZARINDA UMAM",
    "nis": "5251532",
    "nisn": "3199534763",
    "email": "lubna.mirzarinda.umam@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "LU'LU'AH MAKNUNAH FIRDAUSIYAH ABDULLAH",
    "nis": "5261618",
    "nisn": "3180502258",
    "email": "lu.lu.ah.maknunah.firdausiyah.abdullah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "M. FATHIR LUTHFAN SIDI",
    "nis": "5251524",
    "nisn": "3188596234",
    "email": "m.fathir.luthfan.sidi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "M. SAKHA ARKAN MAULANA",
    "nis": "0488",
    "nisn": "3163180662",
    "email": "m.sakha.arkan.maulana@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "M. TEGAR",
    "nis": "0394",
    "nisn": "3178285323",
    "email": "m.tegar@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "M.DIRGANTARA RADIANTO",
    "nis": "0384",
    "nisn": "0166556974",
    "email": "m.dirgantara.radianto@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MAHIRA HANIFAH GHAISANI",
    "nis": "0389",
    "nisn": "0167899477",
    "email": "mahira.hanifah.ghaisani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MAHIRA SHIDQIYYAH AZIZ",
    "nis": "0264",
    "nisn": "0142808832",
    "email": "mahira.shidqiyyah.aziz@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MALIKA ZAHIRA SALAMAH",
    "nis": "5261577",
    "nisn": "3201438480",
    "email": "malika.zahira.salamah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MARIANA ALFA SAEEDA",
    "nis": "0414",
    "nisn": "3148428970",
    "email": "mariana.alfa.saeeda@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MAYESHA RIZANINDYA WARDANA",
    "nis": "5261578",
    "nisn": "3190495721",
    "email": "mayesha.rizanindya.wardana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MAZIDAH ULFA FAKHIRAH AMIN",
    "nis": "0265",
    "nisn": "0155730909",
    "email": "mazidah.ulfa.fakhirah.amin@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MECCALAIBA AKIKO S Y",
    "nis": "5251535",
    "nisn": "3190031753",
    "email": "meccalaiba.akiko.s.y@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MEDINA ELMYRA RASYID",
    "nis": "5251536",
    "nisn": "3188175083",
    "email": "medina.elmyra.rasyid@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MERNANDYA CITRA HIKARI",
    "nis": "0388",
    "nisn": "3169512546",
    "email": "mernandya.citra.hikari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MEYKHAYLA RAMADHANI KIRANA",
    "nis": "5261619",
    "nisn": "3193540797",
    "email": "meykhayla.ramadhani.kirana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MIKAYLA AFKARINA DYANATUL AZIZAH",
    "nis": "5261601",
    "nisn": "3199965864",
    "email": "mikayla.afkarina.dyanatul.azizah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MIKHAYLA FAZA RAMADHANIA ALI",
    "nis": "5251537",
    "nisn": "3195253589",
    "email": "mikhayla.faza.ramadhania.ali@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MIRZA TSAQIB ABQORY",
    "nis": "5251538",
    "nisn": "3187677455",
    "email": "mirza.tsaqib.abqory@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MOH. ADRIELL RAFASYA",
    "nis": "0390",
    "nisn": "3165420924",
    "email": "moh.adriell.rafasya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MOH. ALBY RAFASYA",
    "nis": "0467",
    "nisn": "3171036079",
    "email": "moh.alby.rafasya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MOH. ARSHAKA RAFASYA",
    "nis": "5251539",
    "nisn": "3197085438",
    "email": "moh.arshaka.rafasya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Mohammad Akmal Al Rasyid",
    "nis": "0293",
    "nisn": "0147760945",
    "email": "mohammad.akmal.al.rasyid@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MOHAMMAD MECCA YUDHA WIBOWO",
    "nis": "0468",
    "nisn": "3189238325",
    "email": "mohammad.mecca.yudha.wibowo@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUCH. ABIZAR ALLIE FARISAH",
    "nis": "0490",
    "nisn": "3170622344",
    "email": "much.abizar.allie.farisah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUCHAMMAD ABDULLAH ACHMAD MUGEBEL",
    "nis": "0290",
    "nisn": "0156434903",
    "email": "muchammad.abdullah.achmad.mugebel@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUCHAMMAD ABIZAR RAZKA SABILLA",
    "nis": "5261620",
    "nisn": "3192003458",
    "email": "muchammad.abizar.razka.sabilla@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ABIMANA FATAHILLAH",
    "nis": "0469",
    "nisn": "3173537948",
    "email": "muhammad.abimana.fatahillah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ABIMANA SYAM RAMADHAN HERWANTO",
    "nis": "0267",
    "nisn": "0141411728",
    "email": "muhammad.abimana.syam.ramadhan.herwanto@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ABIZAR ZULKARNAIN",
    "nis": "0383",
    "nisn": "3168025957",
    "email": "muhammad.abizar.zulkarnain@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ADZIQO SYAH KAMIL",
    "nis": "0268",
    "nisn": "3145753639",
    "email": "muhammad.adziqo.syah.kamil@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AFIF RR",
    "nis": "0460",
    "nisn": "3189471806",
    "email": "muhammad.afif.rr@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AKBAR",
    "nis": "0337",
    "nisn": "0152891635",
    "email": "muhammad.akbar@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AKIHIRO AL-ARIEF",
    "nis": "0470",
    "nisn": "3189697922",
    "email": "muhammad.akihiro.al.arief@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AL FATIH",
    "nis": "0471",
    "nisn": "3161246927",
    "email": "muhammad.al.fatih@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AL FATIH AN NAJAH BAHRI",
    "nis": "0466",
    "nisn": "3173143049",
    "email": "muhammad.al.fatih.an.najah.bahri@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ALIF ABDILLAH",
    "nis": "0461",
    "nisn": "3173319739",
    "email": "muhammad.alif.abdillah@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Muhammad Aqmar Izzuddin Aziz",
    "nis": "5251540",
    "nisn": "3182390225",
    "email": "muhammad.aqmar.izzuddin.aziz@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ARFAN RAYHAN",
    "nis": "0332",
    "nisn": "0151301576",
    "email": "muhammad.arfan.rayhan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AYRES SYAHREZA",
    "nis": "5261579",
    "nisn": "3203329799",
    "email": "muhammad.ayres.syahreza@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AZKA AL FATIH",
    "nis": "5261580",
    "nisn": "3205550294",
    "email": "muhammad.azka.al.fatih@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD AZRIL ALHABSYI",
    "nis": "0472",
    "nisn": "3171212934",
    "email": "muhammad.azril.alhabsyi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD DAFFI DWI ARIFANDI",
    "nis": "5251541",
    "nisn": "3188852297",
    "email": "muhammad.daffi.dwi.arifandi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ELKEANU RAVA ANGGARA",
    "nis": "5251543",
    "nisn": "3197243707",
    "email": "muhammad.elkeanu.rava.anggara@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD FAKHRI ZHAFRAN KHAIRY",
    "nis": "0391",
    "nisn": "3165387617",
    "email": "muhammad.fakhri.zhafran.khairy@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD FARIZ JASULI",
    "nis": "0462",
    "nisn": "3178192145",
    "email": "muhammad.fariz.jasuli@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD FAWWAZI ATTALLAH EL KHOIR",
    "nis": "0333",
    "nisn": "3163437080",
    "email": "muhammad.fawwazi.attallah.el.khoir@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Muhammad Gibran yudiesthira",
    "nis": "0269",
    "nisn": "3149267121",
    "email": "muhammad.gibran.yudiesthira@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD HAIDAR IHRAM RAMDHANIYANTO",
    "nis": "0463",
    "nisn": "3176735596",
    "email": "muhammad.haidar.ihram.ramdhaniyanto@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD HANAFI AS SYAFI'I",
    "nis": "0270",
    "nisn": "0144418684",
    "email": "muhammad.hanafi.as.syafi.i@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD HARUN KENZI ALVARO",
    "nis": "0334",
    "nisn": "3153104221",
    "email": "muhammad.harun.kenzi.alvaro@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD HASAN ZAINUL MUTTAQIN",
    "nis": "0271",
    "nisn": "0144949827",
    "email": "muhammad.hasan.zainul.muttaqin@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD HASBI AL MAKKI",
    "nis": "0392",
    "nisn": "3168030953",
    "email": "muhammad.hasbi.al.makki@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD IBRAHIM FIRDAUS",
    "nis": "5261602",
    "nisn": "3197822753",
    "email": "muhammad.ibrahim.firdaus@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD IDRIS HASANI",
    "nis": "5251542",
    "nisn": "3191923076",
    "email": "muhammad.idris.hasani@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD JIRJIES ALI DIMYATI",
    "nis": "5251533",
    "nisn": "3182144226",
    "email": "muhammad.jirjies.ali.dimyati@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD MUBARAK ASY SYAFI'I",
    "nis": "0386",
    "nisn": "0168872825",
    "email": "muhammad.mubarak.asy.syafi.i@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD NAGIB",
    "nis": "0464",
    "nisn": "3172198757",
    "email": "muhammad.nagib@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD NAJIH MUBAROK",
    "nis": "0338",
    "nisn": "0155245900",
    "email": "muhammad.najih.mubarok@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD RAFA AZKA PUTRA",
    "nis": "0335",
    "nisn": "3162855184",
    "email": "muhammad.rafa.azka.putra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD RAZKA TIRTA PRIYANTO",
    "nis": "5261581",
    "nisn": "3191696498",
    "email": "muhammad.razka.tirta.priyanto@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD RESTU ALFARHIZI AW",
    "nis": "0393",
    "nisn": "3172711035",
    "email": "muhammad.restu.alfarhizi.aw@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD SHABIT QEIS SRINARENDRA",
    "nis": "5251534",
    "nisn": "3184832738",
    "email": "muhammad.shabit.qeis.srinarendra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD SUBKI HIDAYAT",
    "nis": "0571",
    "nisn": "3181521923",
    "email": "muhammad.subki.hidayat@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD TAUFIQURROHMAN",
    "nis": "0465",
    "nisn": "3178590167",
    "email": "muhammad.taufiqurrohman@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD URJUAN NABIL SYAHNAWWAZ",
    "nis": "0398",
    "nisn": "3168404202",
    "email": "muhammad.urjuan.nabil.syahnawwaz@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Muhammad Zaidan Ananda",
    "nis": "5251544",
    "nisn": "3187729413",
    "email": "muhammad.zaidan.ananda@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ZAIM ROBBANI",
    "nis": "0395",
    "nisn": "3169822080",
    "email": "muhammad.zaim.robbani@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ZAYDAN FATIH AL KAYYIZ",
    "nis": "5261603",
    "nisn": "3190783611",
    "email": "muhammad.zaydan.fatih.al.kayyiz@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ZAYYAN ARKANA PRATAMA",
    "nis": "5261582",
    "nisn": "3193263948",
    "email": "muhammad.zayyan.arkana.pratama@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUHAMMAD ZULMI ZAINI HAMKA",
    "nis": "5251545",
    "nisn": "3180011794",
    "email": "muhammad.zulmi.zaini.hamka@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUSDALIFAH FIQURROTA AINUN",
    "nis": "0272",
    "nisn": "0145027558",
    "email": "musdalifah.fiqurrota.ainun@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "MUTANABBI MAKHZUMI",
    "nis": "0274",
    "nisn": "0142062379",
    "email": "mutanabbi.makhzumi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "MUTIA KANZA FUROIDA",
    "nis": "0396",
    "nisn": "0167897879",
    "email": "mutia.kanza.furoida@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NABILA ROBIATUS SA'ADAH SUBIANTORO",
    "nis": "0339",
    "nisn": "0165932108",
    "email": "nabila.robiatus.sa.adah.subiantoro@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NADA EMBUN RINJANI",
    "nis": "0341",
    "nisn": "0153986808",
    "email": "nada.embun.rinjani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NADA INDAH THARA JANNAT MAULANA",
    "nis": "5261583",
    "nisn": "3204855899",
    "email": "nada.indah.thara.jannat.maulana@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NADHA FATIMAH SHAZIANUHA",
    "nis": "5261621",
    "nisn": "3192582737",
    "email": "nadha.fatimah.shazianuha@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NADHIFA ARIQA NAILA",
    "nis": "0340",
    "nisn": "0157699287",
    "email": "nadhifa.ariqa.naila@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NADHIFA AYU QANITA",
    "nis": "5261628",
    "nisn": "0152294084",
    "email": "nadhifa.ayu.qanita@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Nadia Putri Ayunda",
    "nis": "0570",
    "nisn": "3173592317",
    "email": "nadia.putri.ayunda@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NADINDRA ALMIRA RINALDY",
    "nis": "5251546",
    "nisn": "3174463237",
    "email": "nadindra.almira.rinaldy@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAFAS BUMI RENGGANIS",
    "nis": "0473",
    "nisn": "3183476555",
    "email": "nafas.bumi.rengganis@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAFIS KEMAL AL KHALIFI",
    "nis": "0397",
    "nisn": "3160854011",
    "email": "nafis.kemal.al.khalifi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "NAFISA SYAFILA ALMAHYRA",
    "nis": "0474",
    "nisn": "3173755154",
    "email": "nafisa.syafila.almahyra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Naira Faza Arsyila",
    "nis": "5251547",
    "nisn": "3188438904",
    "email": "naira.faza.arsyila@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAJWA PRILIA AZARINE",
    "nis": "0273",
    "nisn": "0145799669",
    "email": "najwa.prilia.azarine@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NALATIL IZZAH AR RAMADHANI",
    "nis": "0475",
    "nisn": "3183528216",
    "email": "nalatil.izzah.ar.ramadhani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NASHIF AHMAD NAUVAL RAMADLANIE LZ",
    "nis": "0415",
    "nisn": "0157133095",
    "email": "nashif.ahmad.nauval.ramadlanie.lz@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "NASYA SALSABILA PUTRI",
    "nis": "0275",
    "nisn": "0151312936",
    "email": "nasya.salsabila.putri@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NATASYA NAUFALYNA ASSIFA",
    "nis": "0399",
    "nisn": "3165203104",
    "email": "natasya.naufalyna.assifa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAURA AFIFAH RAMADANI",
    "nis": "0344",
    "nisn": "0153261837",
    "email": "naura.afifah.ramadani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAURA ZAIDA ALMAHYRA",
    "nis": "5261622",
    "nisn": "3206090269",
    "email": "naura.zaida.almahyra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAWRA ALIYAH AZZAHRA",
    "nis": "0276",
    "nisn": "0141061098",
    "email": "nawra.aliyah.azzahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NAYLA NAIMATUS SALSABILA ZAHRA",
    "nis": "0343",
    "nisn": "0164049622",
    "email": "nayla.naimatus.salsabila.zahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NINO SAKHA NUGROHO",
    "nis": "5251548",
    "nisn": "3186889422",
    "email": "nino.sakha.nugroho@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "NUR LAYLI RAMADHANI",
    "nis": "5251549",
    "nisn": "3183432820",
    "email": "nur.layli.ramadhani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NUR MUHAMMAD AZZAMNI",
    "nis": "5261604",
    "nisn": "3199572749",
    "email": "nur.muhammad.azzamni@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "NURHAN AFKAR FADLIL",
    "nis": "0277",
    "nisn": "0136171637",
    "email": "nurhan.afkar.fadlil@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "NURRANIA BATRISYA PUTRI SANJAYA",
    "nis": "0346",
    "nisn": "0156680768",
    "email": "nurrania.batrisya.putri.sanjaya@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "NURUL AINI KARUNIAWAN",
    "nis": "0345",
    "nisn": "0154212635",
    "email": "nurul.aini.karuniawan@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "PRADIPTA ALVARO ANDRIYAN",
    "nis": "0278",
    "nisn": "0141251302",
    "email": "pradipta.alvaro.andriyan@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "PUTRI NADYA ALYSSA SYAFIQA",
    "nis": "0476",
    "nisn": "3182514838",
    "email": "putri.nadya.alyssa.syafiqa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QADISA NADYA PUTRI RACHMANI",
    "nis": "5261623",
    "nisn": "3195893934",
    "email": "qadisa.nadya.putri.rachmani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QALEESYA NAIRA SHANUM",
    "nis": "5251550",
    "nisn": "3180654807",
    "email": "qaleesya.naira.shanum@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QIANA SANDRA ALMAHYRA",
    "nis": "5261605",
    "nisn": "3208934267",
    "email": "qiana.sandra.almahyra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QIANNA RAMADNIYA LATHIFATUNNISA",
    "nis": "0347",
    "nisn": "0169122234",
    "email": "qianna.ramadniya.lathifatunnisa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QIANNO RIFQI ARRAFIF",
    "nis": "0336",
    "nisn": "0131367132",
    "email": "qianno.rifqi.arrafif@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "QIANZY AYESHA SHIRIN",
    "nis": "5261624",
    "nisn": "3198625771",
    "email": "qianzy.ayesha.shirin@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QIANZY RANUPATMA MAHESWARI",
    "nis": "5261584",
    "nisn": "3204736870",
    "email": "qianzy.ranupatma.maheswari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QOTRUNNADA GHASSYA PUTRI",
    "nis": "0279",
    "nisn": "0147886356",
    "email": "qotrunnada.ghassya.putri@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QUEENSHA ADELYA AZKAYRA",
    "nis": "0401",
    "nisn": "3173662020",
    "email": "queensha.adelya.azkayra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Quinn Alesha Kimora Yusuf",
    "nis": "0400",
    "nisn": "3171487747",
    "email": "quinn.alesha.kimora.yusuf@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "QUINZA AZZALEA KIRANA YUSUF",
    "nis": "5251551",
    "nisn": "3196055295",
    "email": "quinza.azzalea.kirana.yusuf@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "R. SULTAN HAMDI MARZUQI",
    "nis": "0477",
    "nisn": "3182189861",
    "email": "r.sultan.hamdi.marzuqi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RAESAKA ABRISAM PUTRA WARDANA",
    "nis": "5251552",
    "nisn": "3170745255",
    "email": "raesaka.abrisam.putra.wardana@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RAFA ALVARONIZAM EL HAZIQ",
    "nis": "0403",
    "nisn": "3166898335",
    "email": "rafa.alvaronizam.el.haziq@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RAFAN ANUGERAH SYAHPUTRA",
    "nis": "5251553",
    "nisn": "3181800003",
    "email": "rafan.anugerah.syahputra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RAFARDHAN ANDRIAN PUTRA",
    "nis": "0348",
    "nisn": "3163732048",
    "email": "rafardhan.andrian.putra@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RAFIFAH ASILA ISMAIL HIDAYAT",
    "nis": "5261606",
    "nisn": "3198842123",
    "email": "rafifah.asila.ismail.hidayat@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RAHMA RIZQINA",
    "nis": "0402",
    "nisn": "3154129777",
    "email": "rahma.rizqina@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Raihanun Medina Ahmad",
    "nis": "0281",
    "nisn": "0151572834",
    "email": "raihanun.medina.ahmad@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RAISA SAFIRA MECCA MUHAMMAD",
    "nis": "0405",
    "nisn": "3164201025",
    "email": "raisa.safira.mecca.muhammad@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RAISHA SABHIRA MISHALL",
    "nis": "5251554",
    "nisn": "3197793459",
    "email": "raisha.sabhira.mishall@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Raisya arsy siraaj",
    "nis": "0404",
    "nisn": "3166704711",
    "email": "raisya.arsy.siraaj@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RAMADHANI GIBRAN SYAHPUTRA NUGROHO",
    "nis": "5261625",
    "nisn": "3199128890",
    "email": "ramadhani.gibran.syahputra.nugroho@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RANDHIKA HANZAH ADHEGUNA",
    "nis": "5251555",
    "nisn": "3195620734",
    "email": "randhika.hanzah.adheguna@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RANITA SEPTIANA BASTIAN",
    "nis": "0349",
    "nisn": "0158769905",
    "email": "ranita.septiana.bastian@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RASYA AGUNG ISMAIL HIDAYAT",
    "nis": "0479",
    "nisn": "3171626207",
    "email": "rasya.agung.ismail.hidayat@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RAZQA FATIH RAMADHANA",
    "nis": "0407",
    "nisn": "3173257454",
    "email": "razqa.fatih.ramadhana@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "Reno Adi Rizki Prasetya",
    "nis": "5251556",
    "nisn": "3173420348",
    "email": "reno.adi.rizki.prasetya@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "REYNAND PRAWIRA ALCANTARA",
    "nis": "5251557",
    "nisn": "3181410461",
    "email": "reynand.prawira.alcantara@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "REYNANDO SEPTIAN ADHEGUNA",
    "nis": "0406",
    "nisn": "0161833240",
    "email": "reynando.septian.adheguna@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "RINJANI JAGAD LINTAR PRAHARANI",
    "nis": "5251558",
    "nisn": "3183605612",
    "email": "rinjani.jagad.lintar.praharani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RINJANI PUTRI RHOMADONA",
    "nis": "0350",
    "nisn": "0152425059",
    "email": "rinjani.putri.rhomadona@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RIYANTI DIRGAHAYU SAVINA",
    "nis": "5251559",
    "nisn": "3189527740",
    "email": "riyanti.dirgahayu.savina@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Rumaisha Aleina Furoida",
    "nis": "5251560",
    "nisn": "0183688471",
    "email": "rumaisha.aleina.furoida@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "RUZBIHAN AL MAKIN AL FAYYADL",
    "nis": "0282",
    "nisn": "0145199470",
    "email": "ruzbihan.al.makin.al.fayyadl@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "SABRINA ANINDITA BASTIAN",
    "nis": "5261607",
    "nisn": "3197275645",
    "email": "sabrina.anindita.bastian@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SABRINA HUMAIRA",
    "nis": "0410",
    "nisn": "0164329441",
    "email": "sabrina.humaira@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SAGA ABRISAM PERMADI",
    "nis": "0408",
    "nisn": "3171051215",
    "email": "saga.abrisam.permadi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "SATYA PRADIPTA HUDA",
    "nis": "5251561",
    "nisn": "3188763407",
    "email": "satya.pradipta.huda@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "SEA SYAKILA ATTARACHMAN",
    "nis": "0481",
    "nisn": "3170090050",
    "email": "sea.syakila.attarachman@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHABIYYA ASHALINA GHOZALI",
    "nis": "0482",
    "nisn": "3189620857",
    "email": "shabiyya.ashalina.ghozali@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHAFEEA AMEERAH RAISHA",
    "nis": "0283",
    "nisn": "0141028651",
    "email": "shafeea.ameerah.raisha@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHAFIRA MEDINA AZZAHRA",
    "nis": "5261585",
    "nisn": "3198488259",
    "email": "shafira.medina.azzahra@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHANUM QUEENARA ANDRIYAN",
    "nis": "5251562",
    "nisn": "3188504633",
    "email": "shanum.queenara.andriyan@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHAQUEENA RAFANIA KHUMAIRA",
    "nis": "0409",
    "nisn": "0169660975",
    "email": "shaqueena.rafania.khumaira@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHAQUEENA ZIA SANDHIKATULLAH",
    "nis": "5251563",
    "nisn": "3181798299",
    "email": "shaqueena.zia.sandhikatullah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHERRIE CELESTIA BERYLL",
    "nis": "5251564",
    "nisn": "3189636699",
    "email": "sherrie.celestia.beryll@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SHOFIE SALSABILA",
    "nis": "0294",
    "nisn": "3146919166",
    "email": "shofie.salsabila@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "SYAHREZA HASBI ABBASY",
    "nis": "0411",
    "nisn": "3168828496",
    "email": "syahreza.hasbi.abbasy@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "SYAQILA FARZANA RAMADHANI",
    "nis": "5251565",
    "nisn": "3188955756",
    "email": "syaqila.farzana.ramadhani@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Tatiana Hilma",
    "nis": "0484",
    "nisn": "3176563381",
    "email": "tatiana.hilma@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "THOIFATUZ ZAHIRO",
    "nis": "0285",
    "nisn": "0143294657",
    "email": "thoifatuz.zahiro@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "TSAQIF MAULANA EL RUMI",
    "nis": "5261586",
    "nisn": "3190868125",
    "email": "tsaqif.maulana.el.rumi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "TSAQIF ZIAUL HAQUE",
    "nis": "5261608",
    "nisn": "3199995360",
    "email": "tsaqif.ziaul.haque@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "VANDA KAHISHA YULIASARI",
    "nis": "5261587",
    "nisn": "3180283334",
    "email": "vanda.kahisha.yuliasari@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "VINNYNOVA AIDA HIQMAFIDAH",
    "nis": "0286",
    "nisn": "0142854076",
    "email": "vinnynova.aida.hiqmafidah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ZAHRO' ABDULLAH MUGEBEL",
    "nis": "0412",
    "nisn": "3171145329",
    "email": "zahro.abdullah.mugebel@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ZAIGHAM FALETEHAN FAUZI",
    "nis": "0491",
    "nisn": "3178935357",
    "email": "zaigham.faletehan.fauzi@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ZAKY AHMAD ELGANI",
    "nis": "0288",
    "nisn": "0154043244",
    "email": "zaky.ahmad.elgani@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ZALIKHA FATIMAH QAIREENNISA",
    "nis": "0289",
    "nisn": "0142795456",
    "email": "zalikha.fatimah.qaireennisa@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ZIANKA ANINDYA FAUZI",
    "nis": "0485",
    "nisn": "3185459669",
    "email": "zianka.anindya.fauzi@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "Zulfa Tazkiyah",
    "nis": "0486",
    "nisn": "3177957300",
    "email": "zulfa.tazkiyah@gmail.com",
    "unit": "sd namira",
    "keterangan": "P"
  },
  {
    "nama": "ZULFIKAR ALI SYABBANA",
    "nis": "0487",
    "nisn": "3170688072",
    "email": "zulfikar.ali.syabbana@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  },
  {
    "nama": "ZYANDRU WAFDA MULKILAKBAR",
    "nis": "5251566",
    "nisn": "3180753518",
    "email": "zyandru.wafda.mulkilakbar@gmail.com",
    "unit": "sd namira",
    "keterangan": "L"
  }
];

        foreach ($studentsData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $nis = trim($item['nis']);
            $nisn = trim($item['nisn']);
            $gender = trim($item['keterangan']);

            $password = $nis ? $nis : ($nisn ? $nisn : 'siswa123');

            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
            }

            if ($roleId) {
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                    'team_id' => $unitId,
                ]);
            }

            Student::updateOrCreate([
                'user_id' => $user->id,
                'unit_id' => $unitId,
            ], [
                'full_name' => $name,
                'nis' => $nis,
                'nisn' => $nisn,
                'gender' => $gender,
            ]);
        }
        echo "Berhasil mengimpor 340 Siswa SD Namira!\n";
    }
}
