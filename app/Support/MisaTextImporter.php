<?php

namespace App\Support;

use App\Models\Mass;

/**
 * Memotong teks misa lengkap (hasil copy dari Word/PDF) menjadi
 * item rundown per header liturgi: LAGU PEMBUKA, TOBAT, dst.
 */
class MisaTextImporter
{
    /** Header yang dikenali. Header lain yang mirip (awalan sama) juga diterima. */
    public const KNOWN_HEADERS = [
        'PRA-LITURGI', 'PRA- LITURGI', 'RITUS PEMBUKA', 'LAGU PEMBUKA', 'ANTIFON PEMBUKA',
        'TANDA SALIB DAN SALAM PEMBUKA', 'TANDA SALIB', 'SALAM PEMBUKA', 'PENGANTAR',
        'TOBAT', 'DOA TOBAT', 'ABSOLUSI', 'TUHAN KASIHANILAH KAMI', 'KEMULIAAN',
        'DOA PEMBUKA', 'LITURGI SABDA', 'BACAAN PERTAMA', 'BACAAN I', 'MAZMUR TANGGAPAN',
        'BACAAN KEDUA', 'BACAAN II', 'BAIT PENGANTAR INJIL', 'ALLELUYA', 'BACAAN INJIL',
        'INJIL', 'HOMILI', 'SYAHADAT', 'SYAHADAT SINGKAT', 'AKU PERCAYA', 'DOA UMAT',
        'LITURGI EKARISTI', 'PERSIAPAN PERSEMBAHAN', 'LAGU PERSEMBAHAN',
        'MENGHUNJUKKAN BAHAN PERSEMBAHAN', 'DOA PERSIAPAN PERSEMBAHAN',
        'DOA SYUKUR AGUNG', 'PREFASI', 'KUDUS', 'BAPA KAMI', 'EMBOLISME', 'DOA DAMAI',
        'SALAM DAMAI', 'ANAK DOMBA ALLAH', 'PERSIAPAN KOMUNI', 'KOMUNI', 'LAGU KOMUNI',
        'ANTIFON SESUDAH KOMUNI', 'DOA SESUDAH KOMUNI', 'RITUS PENUTUP', 'PENGUMUMAN',
        'BERKAT', 'PENGUTUSAN', 'LAGU PENUTUP', 'PERAYAAN EKARISTI',
    ];

    /** Baris petunjuk sikap yang dijadikan blok kecil sendiri. */
    private const CUES = ['BERDIRI', 'DUDUK', 'BERLUTUT'];

    /**
     * @return array<int, array{header: ?string, title: ?string, body: string, title_only: bool}>
     */
    public function parse(string $text): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $text));
        $sections = [];
        $current = null;

        foreach ($lines as $raw) {
            $t = trim($raw);

            if ($t !== '' && $this->isHeader($t)) {
                if ($current !== null) {
                    $sections[] = $this->finalize($current);
                }
                $current = ['header' => preg_replace('/\s+/', ' ', $t), 'lines' => []];
                continue;
            }

            if ($current === null) {
                if ($t === '') {
                    continue;
                }
                $current = ['header' => null, 'lines' => []];
            }

            $current['lines'][] = $t;
        }

        if ($current !== null) {
            $sections[] = $this->finalize($current);
        }

        return array_values(array_filter(
            $sections,
            fn (array $s) => $s['header'] !== null || $s['body'] !== '' || $s['title'] !== null
        ));
    }

    /** Import hasil parse menjadi item rundown pada sebuah misa. */
    public function import(Mass $mass, string $text): int
    {
        $sort = (int) $mass->items()->max('sort');
        $count = 0;

        foreach ($this->parse($text) as $section) {
            $mass->items()->create([
                'sort' => ++$sort,
                'header' => $section['header'],
                'title' => $section['title'],
                'body' => $section['body'],
                'display' => 'both',
                'title_only' => $section['title_only'],
            ]);
            $count++;
        }

        return $count;
    }

    private function isHeader(string $t): bool
    {
        if (preg_match('/[a-z]/', $t) || mb_strlen($t) > 60) {
            return false;
        }

        $u = preg_replace('/\s+/', ' ', mb_strtoupper(trim($t, " \t:.")));

        foreach (self::KNOWN_HEADERS as $known) {
            if ($u === $known || str_starts_with($u, $known.' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{header: ?string, lines: array<int, string>} $section
     * @return array{header: ?string, title: ?string, body: string, title_only: bool}
     */
    private function finalize(array $section): array
    {
        $lines = $section['lines'];

        // Buang baris kosong di awal/akhir
        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }
        while ($lines !== [] && trim(end($lines)) === '') {
            array_pop($lines);
        }

        // Petunjuk sikap → beri kurung agar tampil sebagai cue kecil
        $lines = array_map(function (string $line) {
            $u = mb_strtoupper(trim($line));
            return in_array($u, self::CUES, true) ? '('.ucfirst(mb_strtolower($u)).')' : $line;
        }, $lines);

        // Deteksi judul lagu: baris pertama pendek, bukan kapital semua,
        // hanya untuk section lagu/nyanyian
        $title = null;
        $header = $section['header'] ?? '';
        if ($lines !== [] && str_contains(mb_strtoupper($header), 'LAGU')) {
            $first = trim($lines[0]);
            if ($first !== '' && mb_strlen($first) <= 60
                && $first !== mb_strtoupper($first)
                && ! preg_match('/[.:;,]$/', $first)) {
                $title = $first;
                array_shift($lines);
                while ($lines !== [] && trim($lines[0]) === '') {
                    array_shift($lines);
                }
            }
        }

        // Rapikan: maksimal satu baris kosong beruntun
        $body = preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines));
        $body = trim($body ?? '');

        return [
            'header' => $section['header'],
            'title' => $title,
            'body' => $body,
            'title_only' => $body === '',
        ];
    }
}
