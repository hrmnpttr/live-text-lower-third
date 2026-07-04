<?php

namespace App\Support;

use App\Models\LibraryItem;
use PDO;

/**
 * Import lagu dari database EasyWorship 6/7:
 *   Songs.db     — judul & metadata (tabel berisi kolom "title")
 *   SongWords.db — lirik dalam format RTF (tabel berisi kolom "words")
 * Biasanya ada di: C:\Users\Public\Documents\Softouch\EasyWorship\
 *   Default Profile\v6.1\Databases\Data\
 * Teks RTF dikupas menjadi teks polos; tiap slide menjadi satu bagian.
 */
class EasyWorshipImporter
{
    /** @return array{imported: int, skipped: int} */
    public function import(string $songsDbPath, string $wordsDbPath): array
    {
        $songs = $this->readTable($songsDbPath, 'title');
        $words = $this->readTable($wordsDbPath, 'words');

        // Petakan lirik berdasarkan song_id / rowid
        $wordsBySong = [];
        foreach ($words as $row) {
            $key = $row['song_id'] ?? $row['rowid'];
            $wordsBySong[$key] = $row['words'];
        }

        $imported = 0;
        $skipped = 0;

        foreach ($songs as $song) {
            $title = trim((string) ($song['title'] ?? ''));
            $rtf = $wordsBySong[$song['rowid']] ?? null;

            if ($title === '' || ! $rtf) {
                $skipped++;
                continue;
            }

            $text = $this->rtfToText($rtf);
            if (trim($text) === '') {
                $skipped++;
                continue;
            }

            $sections = [];
            $slides = preg_split('/\n\s*\n/', trim($text));
            foreach ($slides as $i => $slide) {
                $slide = trim($slide);
                if ($slide !== '') {
                    $sections[] = ['name' => 'Bagian '.($i + 1), 'notation' => null, 'body' => $slide];
                }
            }

            if ($sections === []) {
                $skipped++;
                continue;
            }

            LibraryItem::updateOrCreate(
                ['type' => 'lagu', 'title' => $title, 'code' => null],
                [
                    'sections' => $sections,
                    'tags' => ['easyworship'],
                    'set_name' => null,
                ]
            );
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Baca tabel pertama yang punya kolom tertentu (skema EW berubah-ubah antar versi).
     *
     * @return array<int, array<string, mixed>>
     */
    private function readTable(string $dbPath, string $mustHaveColumn): array
    {
        $pdo = new PDO('sqlite:'.$dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $columns = $pdo->query('PRAGMA table_info("'.str_replace('"', '', $table).'")')
                ->fetchAll(PDO::FETCH_ASSOC);
            $names = array_map(fn ($c) => strtolower($c['name']), $columns);

            if (in_array($mustHaveColumn, $names, true)) {
                return $pdo->query('SELECT rowid, * FROM "'.str_replace('"', '', $table).'"')
                    ->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        throw new \RuntimeException("Tidak menemukan tabel dengan kolom \"{$mustHaveColumn}\" di ".basename($dbPath).'. Pastikan file database EasyWorship yang benar.');
    }

    /** Konversi RTF EasyWorship ke teks polos. */
    public function rtfToText(string $rtf): string
    {
        // Buang grup meta (font, warna, dsb.) — termasuk grup bersarang satu tingkat
        $rtf = preg_replace('/\{\\\\(?:fonttbl|colortbl|stylesheet|info|\*)(?:[^{}]|\{[^{}]*\})*\}/is', '', $rtf) ?? $rtf;

        // Pergantian baris/paragraf/slide
        $rtf = preg_replace('/\\\\(?:par|line|sdewslide|page|sect)\b/i', "\n", $rtf) ?? $rtf;

        // Karakter escape \'xx (Windows-1252)
        $rtf = preg_replace_callback("/\\\\'([0-9a-fA-F]{2})/", function ($m) {
            return mb_convert_encoding(chr(hexdec($m[1])), 'UTF-8', 'Windows-1252');
        }, $rtf) ?? $rtf;

        // Unicode escape \uNNNN
        $rtf = preg_replace_callback('/\\\\u(-?\d+)\s?\??/', function ($m) {
            $code = (int) $m[1];
            if ($code < 0) {
                $code += 65536;
            }
            return mb_chr($code, 'UTF-8');
        }, $rtf) ?? $rtf;

        // Sisa control word & simbol
        $rtf = preg_replace('/\\\\[a-z]+-?\d* ?/i', '', $rtf) ?? $rtf;
        $rtf = str_replace(['{', '}', '\\'], '', $rtf);

        // Rapikan spasi & baris
        $lines = array_map('trim', explode("\n", $rtf));
        $text = implode("\n", $lines);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
