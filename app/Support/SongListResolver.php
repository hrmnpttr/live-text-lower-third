<?php

namespace App\Support;

use App\Models\LibraryItem;
use App\Models\Mass;

/**
 * Menerjemahkan daftar singkat dari petugas menjadi rundown, contoh:
 *
 *   pembuka 300
 *   mazmur 815
 *   misa kita 4
 *   persembahan 380
 *   komuni 425 428
 *   penutup 500
 *
 * Angka dicari di bank berdasarkan kode (default buku PS; bisa "mb 401").
 * "misa kita 4" memasang satu set ordinarium (Tuhan Kasihanilah, Kemuliaan,
 * Kudus, Bapa Kami, Anak Domba Allah) dari bank dengan set_name tersebut.
 */
class SongListResolver
{
    private const SLOTS = [
        'pembuka' => 'LAGU PEMBUKA',
        'perarakan' => 'LAGU PEMBUKA',
        'mazmur' => 'MAZMUR TANGGAPAN',
        'persembahan' => 'LAGU PERSEMBAHAN',
        'komuni' => 'LAGU KOMUNI',
        'madah syukur' => 'MADAH SYUKUR',
        'penutup' => 'LAGU PENUTUP',
    ];

    private const ORDINARIUM = [
        'tuhan kasihanilah' => 'TUHAN KASIHANILAH KAMI',
        'kemuliaan' => 'KEMULIAAN',
        'kudus' => 'KUDUS',
        'bapa kami' => 'BAPA KAMI',
        'anak domba' => 'ANAK DOMBA ALLAH',
    ];

    /**
     * @return array{ok: array<int, string>, missing: array<int, string>}
     */
    public function apply(Mass $mass, string $text): array
    {
        $report = ['ok' => [], 'missing' => []];

        foreach (explode("\n", str_replace("\r\n", "\n", $text)) as $line) {
            $line = trim($line, " \t-•·");
            if ($line === '') {
                continue;
            }

            $lower = mb_strtolower($line);

            // Set ordinarium: "misa kita 4", "ordinarium: misa senja"
            if (preg_match('/^(?:ordinarium[:\s]+)?(misa\s+.+)$/i', $line, $m)) {
                $this->applyOrdinariumSet($mass, trim($m[1]), $report);
                continue;
            }

            // Baris slot: "pembuka 300", "komuni 425 428", "penutup ps 500"
            $matched = false;
            foreach (self::SLOTS as $keyword => $header) {
                if (str_starts_with($lower, $keyword)) {
                    $rest = trim(mb_substr($line, mb_strlen($keyword)), " \t:.-");
                    $this->applySlot($mass, $header, $rest, $report);
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                $report['missing'][] = $line.' (baris tidak dikenali)';
            }
        }

        return $report;
    }

    private function applySlot(Mass $mass, string $header, string $rest, array &$report): void
    {
        // Beberapa nomor dalam satu baris → beberapa lagu untuk slot yang sama
        preg_match_all('/(?:(ps|mb|pk)\s*)?(\d{1,4}[a-z]?)/i', $rest, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            // Bukan nomor — coba cari judul
            $item = $rest === '' ? null
                : LibraryItem::where('title', 'like', '%'.$rest.'%')->first();
            if ($item) {
                $this->assign($mass, $header, $item);
                $report['ok'][] = $header.' → '.$item->displayTitle();
            } else {
                $report['missing'][] = $header.' → "'.$rest.'" tidak ada di bank';
            }

            return;
        }

        $first = true;
        foreach ($matches as $m) {
            $item = $this->findByCode($m[1] ?: 'PS', $m[2]);
            if ($item) {
                $this->assign($mass, $header, $item, appendAlways: ! $first);
                $report['ok'][] = $header.' → '.$item->displayTitle();
            } else {
                $report['missing'][] = $header.' → '.strtoupper($m[1] ?: 'PS').' '.$m[2].' belum ada di bank';
            }
            $first = false;
        }
    }

    private function applyOrdinariumSet(Mass $mass, string $setName, array &$report): void
    {
        foreach (self::ORDINARIUM as $needle => $header) {
            $item = LibraryItem::query()
                ->whereIn('type', ['ordinarium', 'doa'])
                ->where('set_name', 'like', $setName.'%')
                ->where('title', 'like', '%'.$needle.'%')
                ->first();

            if ($item) {
                $this->assign($mass, $header, $item);
                $report['ok'][] = $header.' → '.$item->displayTitle().' ('.$item->set_name.')';
            } else {
                $report['missing'][] = $header.' → set "'.$setName.'" belum ada di bank';
            }
        }
    }

    private function findByCode(string $book, string $number): ?LibraryItem
    {
        $book = strtoupper($book);
        $code = $book.' '.$number;

        return LibraryItem::where('code', $code)->first()
            ?? LibraryItem::whereRaw("UPPER(REPLACE(code, ' ', '')) = ?", [$book.strtoupper($number)])->first();
    }

    /** Pasang ke item rundown ber-header sama; kalau tidak ada, tambah di akhir. */
    private function assign(Mass $mass, string $header, LibraryItem $li, bool $appendAlways = false): void
    {
        $existing = $appendAlways ? null : $mass->items()->where('header', $header)->first();

        if ($existing) {
            $existing->update([
                'library_item_id' => $li->id,
                'title' => $li->displayTitle(),
                'title_only' => false,
                'body' => null,
            ]);
        } else {
            $mass->items()->create([
                'sort' => (int) $mass->items()->max('sort') + 1,
                'header' => $header,
                'title' => $li->displayTitle(),
                'library_item_id' => $li->id,
                'display' => 'both',
            ]);
        }
    }
}
