<?php

namespace Database\Seeders;

use App\Models\LibraryItem;
use App\Models\LiveState;
use App\Models\Mass;
use App\Models\Theme;
use App\Support\MisaTextImporter;
use Illuminate\Database\Seeder;

class LiturgiaSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Tema warna liturgi ----------
        $themes = [
            ['name' => 'Masa Biasa', 'color_key' => 'hijau', 'accent' => '#74c07a', 'bg_tint' => 'rgba(13,38,21,.92)', 'is_default' => true],
            ['name' => 'Adven / Prapaskah', 'color_key' => 'ungu', 'accent' => '#a678d6', 'bg_tint' => 'rgba(28,16,46,.92)'],
            ['name' => 'Natal / Paskah', 'color_key' => 'putih', 'accent' => '#e3cf8f', 'bg_tint' => 'rgba(30,31,38,.94)'],
            ['name' => 'Pentakosta / Minggu Palma', 'color_key' => 'merah', 'accent' => '#e05c5c', 'bg_tint' => 'rgba(52,14,16,.92)'],
            ['name' => 'Gaudete / Laetare', 'color_key' => 'pink', 'accent' => '#e592b0', 'bg_tint' => 'rgba(46,15,29,.92)'],
            ['name' => 'Emas — Perayaan Agung', 'color_key' => 'custom', 'accent' => '#d4af37', 'bg_tint' => 'rgba(14,11,5,.93)'],
        ];
        $themeIds = [];
        foreach ($themes as $t) {
            $themeIds[$t['color_key']] = Theme::firstOrCreate(['name' => $t['name']], $t)->id;
        }

        // ---------- Bank konten: ordinarium ----------
        $tuhanKasihanilah = LibraryItem::firstOrCreate(
            ['type' => 'ordinarium', 'title' => 'Tuhan Kasihanilah Kami'],
            ['set_name' => 'Misa Kita 4', 'sections' => [[
                'name' => null,
                'notation' => null,
                'body' => "Tuhan kasihanilah kami (2x)\nKristus kasihanilah kami (2x)\nTuhan kasihanilah kami (2x)",
            ]]]
        );

        $kemuliaan = LibraryItem::firstOrCreate(
            ['type' => 'ordinarium', 'title' => 'Kemuliaan'],
            ['set_name' => 'Misa Kita 4', 'sections' => [[
                'name' => null,
                'notation' => null,
                'body' => "Kemuliaan kepada Allah di surga,\ndan damai di bumi,\ndan damai di bumi kepada orang yang berkenan kepada-Nya.\n\nKami memuji Dikau. Kami meluhurkan Dikau.\nKami menyembah Dikau. Kami memuliakan Dikau.\nKami bersyukur, kami bersyukur. Kami bersyukur pada-Mu.\nKarena kemuliaan-Mu yang besar.\n\nYa Tuhan Allah Raja Surgawi, Allah Bapa Yang Mahakuasa.\nYa Tuhan Yesus Kristus, Putera yang tunggal.\nYa Tuhan Allah, Anak Domba Allah, Putra Bapa.\n\nEngkau yang menghapus dosa dunia, kasihanilah kami.\nEngkau yang menghapus dosa dunia, kabulkanlah doa kami.\nEngkau yang duduk di sisi Bapa, kasihanilah kami.\n\nKar'na hanya Engkaulah Kudus.\nHanya Engkaulah Tuhan.\nHanya Engkaulah Mahatinggi, ya Yesus Kristus,\nbersama dengan Roh Kudus,\ndalam kemuliaan Allah Bapa. Amin.",
            ]]]
        );

        $bapaKami = LibraryItem::firstOrCreate(
            ['type' => 'doa', 'title' => 'Bapa Kami'],
            ['sections' => [[
                'name' => null,
                'notation' => null,
                'body' => "Bapa kami yang ada di surga,\ndimuliakanlah nama-Mu.\nDatanglah kerajaan-Mu.\nJadilah kehendak-Mu\ndi atas bumi seperti di dalam surga.\n\nBerilah kami rezeki pada hari ini,\ndan ampunilah kesalahan kami,\nseperti kami pun mengampuni\nyang bersalah kepada kami.\nDan janganlah masukkan kami\nke dalam pencobaan,\ntetapi bebaskanlah kami dari yang jahat.",
            ]]]
        );

        LibraryItem::firstOrCreate(
            ['type' => 'ordinarium', 'title' => 'Kudus'],
            ['set_name' => 'Misa Kita 4', 'sections' => [[
                'name' => null,
                'notation' => null,
                'body' => "Kudus, kudus, kuduslah Tuhan,\nAllah segala kuasa.\nSurga dan bumi penuh kemuliaan-Mu.\nTerpujilah Engkau di surga.\n\nDiberkatilah yang datang\ndalam nama Tuhan.\nTerpujilah Engkau di surga.",
            ]]]
        );

        $anakDomba = LibraryItem::firstOrCreate(
            ['type' => 'ordinarium', 'title' => 'Anak Domba Allah'],
            ['set_name' => 'Misa Kita 4', 'sections' => [[
                'name' => null,
                'notation' => null,
                'body' => "Anak domba Allah\nyang menghapus dosa dunia,\nkasihanilah kami. (2x)\n\nAnak domba Allah\nyang menghapus dosa dunia,\nberilah kami damai.",
            ]]]
        );

        // ---------- Bank konten: Mazmur 92 dengan not angka ----------
        $mazmur92 = LibraryItem::firstOrCreate(
            ['type' => 'mazmur', 'title' => 'Sungguh baik menyanyikan syukur kepada-Mu ya Tuhan'],
            [
                'code' => 'Mzm 92:2-3.13-14.15-16',
                'sections' => [
                    [
                        'name' => 'Refren',
                        'notation' => "not: 1 2 | 3 3 [.3] ([43]) ([43] [46]) | 5 5 [.5] ([43]) ([42]) |\nsyl: Sung- guh | ba- ik me- nya- nyi- kan | syu- kur ke- pa- da- |\nnot: 3 . ([21]) ([[2127,]]) | 1 . . ||\nsyl: Mu _ ya Tu- | han. _ _",
                        'body' => null,
                    ],
                    [
                        'name' => 'Ayat 1',
                        'notation' => null,
                        'body' => "Sungguh baik menyanyikan syukur kepada Tuhan,\ndan menyanyikan mazmur bagi nama-Mu, ya Yang Mahatinggi,\nmemberitakan kasih setia-Mu di waktu pagi\ndan kesetiaan-Mu di waktu malam.",
                    ],
                    [
                        'name' => 'Ayat 2',
                        'notation' => null,
                        'body' => "Orang benar akan bertunas seperti pohon kurma,\nakan tumbuh subur seperti pohon aras di Libanon,\nmereka yang ditanam di Bait Tuhan,\nakan bertunas di pelataran Allah kita.",
                    ],
                    [
                        'name' => 'Ayat 3',
                        'notation' => null,
                        'body' => "Pada masa tua pun mereka masih berbuah\nmenjadi gemuk dan segar,\nuntuk memberitakan bahwa Tuhan itu benar,\nbahwa Ia Gunung Batuku,\ndan tidak ada kecurangan pada-Nya.",
                    ],
                ],
            ]
        );

        // ---------- Bank konten: contoh lagu (placeholder — isi dengan lagu paroki Anda) ----------
        $laguPembuka = LibraryItem::firstOrCreate(
            ['type' => 'lagu', 'title' => 'Contoh Lagu Pembuka'],
            ['sections' => [
                ['name' => 'Bait 1', 'notation' => null, 'body' => "Ini contoh bait pertama,\nganti dengan lirik lagu Anda.\nSatu blok berisi dua baris,\nagar nyaman dibaca umat."],
                ['name' => 'Refren', 'notation' => null, 'body' => "Ini contoh refren lagu,\nblok dipisah baris kosong."],
            ]]
        );

        // ---------- Template misa default (urutan TPE) ----------
        if (! Mass::where('is_template', true)->exists()) {
            $template = Mass::create([
                'title' => 'TEMPLATE — Misa Mingguan',
                'is_template' => true,
                'theme_id' => $themeIds['hijau'],
                'notes' => 'Duplikat template ini tiap minggu, lalu isi lagu/bacaan per misa.',
            ]);

            $items = [
                ['header' => 'LAGU PEMBUKA', 'library_item_id' => $laguPembuka->id],
                ['header' => 'PERAYAAN EKARISTI', 'title' => 'Hari Minggu Biasa ke-…', 'title_only' => true],
                ['header' => 'TOBAT', 'body' => "Saya mengaku kepada Allah yang Mahakuasa,\ndan kepada Saudara sekalian,\nbahwa saya telah berdosa\ndengan pikiran dan perkataan,\ndengan perbuatan dan kelalaian.\n\nSaya berdosa, saya berdosa,\nsaya sungguh berdosa.\nOleh sebab itu saya mohon\nkepada Santa Perawan Maria,\nkepada para malaikat dan orang kudus,\ndan kepada Saudara sekalian,\nsupaya mendoakan saya\npada Allah, Tuhan kita."],
                ['header' => 'TUHAN KASIHANILAH KAMI', 'library_item_id' => $tuhanKasihanilah->id],
                ['header' => 'KEMULIAAN', 'library_item_id' => $kemuliaan->id],
                ['header' => 'DOA PEMBUKA', 'title' => 'Hari Minggu Biasa ke-…', 'title_only' => true],
                ['header' => 'BACAAN PERTAMA', 'title' => 'Kejadian …', 'title_only' => true],
                ['header' => 'MAZMUR TANGGAPAN', 'library_item_id' => $mazmur92->id],
                ['header' => 'BAIT PENGANTAR INJIL', 'body' => "Alleluya, alleluya, alleluya."],
                ['header' => 'BACAAN INJIL', 'title' => 'Injil …', 'title_only' => true],
                ['header' => 'HOMILI', 'title_only' => true],
                ['header' => 'AKU PERCAYA', 'body' => "Aku percaya akan Allah,\nBapa yang Mahakuasa,\npencipta langit dan bumi.\n\nDan akan Yesus Kristus,\nPutra-Nya yang tunggal, Tuhan kita,\nyang dikandung dari Roh Kudus,\ndilahirkan oleh Perawan Maria.\n\nYang menderita sengsara\ndalam pemerintahan Pontius Pilatus,\ndisalibkan, wafat, dan dimakamkan,\nyang turun ke tempat penantian,\npada hari ketiga bangkit\ndari antara orang mati.\n\nYang naik ke surga,\nduduk di sebelah kanan Allah Bapa\nyang Mahakuasa,\ndari situ Ia akan datang\nmengadili orang yang hidup dan yang mati.\n\nAku percaya akan Roh Kudus,\nGereja Katolik yang kudus,\npersekutuan para kudus,\npengampunan dosa,\nkebangkitan badan,\nkehidupan kekal. Amin."],
                ['header' => 'DOA UMAT', 'title_only' => true],
                ['header' => 'LAGU PERSEMBAHAN', 'title' => '…', 'title_only' => true],
                ['header' => 'KUDUS', 'body' => "Kudus, kudus, kuduslah Tuhan,\nAllah segala kuasa.\nSurga dan bumi penuh kemuliaan-Mu.\nTerpujilah Engkau di surga.\n\nDiberkatilah yang datang\ndalam nama Tuhan.\nTerpujilah Engkau di surga."],
                ['header' => 'BAPA KAMI', 'library_item_id' => $bapaKami->id],
                ['header' => 'ANAK DOMBA ALLAH', 'library_item_id' => $anakDomba->id],
                ['header' => 'LAGU KOMUNI', 'title' => '…', 'title_only' => true],
                ['header' => 'PENGUMUMAN', 'title_only' => true],
                ['header' => 'BERKAT', 'title_only' => true],
                ['header' => 'LAGU PENUTUP', 'title' => '…', 'title_only' => true],
            ];

            foreach ($items as $i => $item) {
                $template->items()->create($item + ['sort' => $i + 1, 'display' => 'both', 'title_only' => $item['title_only'] ?? false]);
            }
        }

        // ---------- Contoh import otomatis (opsional) ----------
        // Letakkan teks misa lengkap Anda di database/seeders/data/misa.txt
        // untuk melihat hasil potong otomatis importer saat seeding.
        $tpePath = __DIR__.'/data/misa.txt';
        if (is_file($tpePath) && ! Mass::where('title', 'like', 'Contoh Import%')->exists()) {
            $mass = Mass::create([
                'title' => 'Contoh Import — Misa Lengkap',
                'theme_id' => $themeIds['putih'],
                'notes' => 'Hasil potong otomatis dari teks misa lengkap (importer).',
            ]);
            app(MisaTextImporter::class)->import($mass, file_get_contents($tpePath));
        }

        LiveState::current();
    }
}
