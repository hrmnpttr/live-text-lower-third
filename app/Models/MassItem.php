<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MassItem extends Model
{
    protected $fillable = [
        'mass_id', 'sort', 'header', 'title', 'library_item_id',
        'section_index', 'body', 'notation', 'image_path', 'display', 'title_only',
    ];

    protected $casts = [
        'title_only' => 'boolean',
    ];

    public function mass(): BelongsTo
    {
        return $this->belongsTo(Mass::class);
    }

    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class);
    }

    /**
     * Kompilasi item menjadi daftar blok tampil.
     * Blok teks:    {label?, type: "text", text}
     * Blok notasi:  {label?, type: "not", lines: [{not, syl}]}
     */
    public function toCompiledArray(): array
    {
        $blocks = [];
        $title = $this->title;

        if ($this->libraryItem) {
            $title = $title ?: $this->libraryItem->displayTitle();
            $sections = $this->libraryItem->sections ?? [];

            if ($this->section_index !== null && isset($sections[$this->section_index])) {
                $sections = [$sections[$this->section_index]];
            }

            foreach ($sections as $section) {
                $label = $section['name'] ?? null;
                foreach (self::compileNotation($section['notation'] ?? '') as $block) {
                    $block['label'] = $label;
                    $blocks[] = $block;
                    $label = null; // label hanya pada blok pertama section
                }
                foreach (self::splitTextBlocks($section['body'] ?? '') as $text) {
                    $blocks[] = ['label' => $label, 'type' => 'text', 'text' => $text];
                    $label = null;
                }
            }
        }

        foreach (self::compileNotation($this->notation ?? '') as $block) {
            $blocks[] = $block;
        }

        foreach (self::splitTextBlocks($this->body ?? '') as $text) {
            $blocks[] = ['type' => 'text', 'text' => $text];
        }

        if ($this->title_only || ($blocks === [] && ! $this->image_path)) {
            $blocks = [['type' => 'text', 'text' => $title ?: ($this->header ?: '')]];
        }

        // Gambar full layar (thumbnail YouTube, poster) — jadi blok pertama
        if ($this->image_path) {
            array_unshift($blocks, [
                'type' => 'img',
                'src' => asset('storage/'.$this->image_path),
            ]);
        }

        return [
            'id' => $this->id,
            'header' => $this->header,
            'title' => $title,
            'display' => $this->display,
            'title_only' => $this->title_only,
            'blocks' => array_values($blocks),
        ];
    }

    /**
     * Pecah teks jadi blok per paragraf (baris kosong = pemisah).
     * Auto-rapi: target 2 baris per blok. Paragraf ≤3 baris dibiarkan
     * (bait 3 baris yang disengaja tetap utuh); lebih dari itu dipecah
     * per 2 baris, dan sisa 1 baris digabung ke blok sebelumnya (jadi 3)
     * agar tidak ada blok sebaris sendirian.
     */
    public static function splitTextBlocks(?string $text): array
    {
        if (! $text || trim($text) === '') {
            return [];
        }

        $parts = preg_split('/\n\s*\n/', str_replace("\r\n", "\n", trim($text)));
        $parts = array_values(array_filter(array_map('trim', $parts)));

        $blocks = [];
        foreach ($parts as $part) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $part)), fn ($l) => $l !== ''));

            if (count($lines) <= 3) {
                $blocks[] = implode("\n", $lines);
                continue;
            }

            $chunks = array_chunk($lines, 2);
            $last = count($chunks) - 1;
            if ($last > 0 && count($chunks[$last]) === 1) {
                $chunks[$last - 1] = array_merge($chunks[$last - 1], $chunks[$last]);
                array_pop($chunks);
            }
            foreach ($chunks as $chunk) {
                $blocks[] = implode("\n", $chunk);
            }
        }

        return $blocks;
    }

    /**
     * Parse markup not angka. Baris "not:" dan "syl:" berpasangan,
     * baris kosong memisahkan blok notasi.
     */
    public static function compileNotation(?string $markup): array
    {
        if (! $markup || trim($markup) === '') {
            return [];
        }

        $blocks = [];
        $chunks = preg_split('/\n\s*\n/', str_replace("\r\n", "\n", trim($markup)));

        foreach ($chunks as $chunk) {
            $lines = [];
            $pending = null;

            foreach (explode("\n", trim($chunk)) as $line) {
                $line = trim($line);
                if (str_starts_with($line, 'not:')) {
                    if ($pending !== null) {
                        $lines[] = ['not' => $pending, 'syl' => ''];
                    }
                    $pending = trim(substr($line, 4));
                } elseif (str_starts_with($line, 'syl:')) {
                    $lines[] = ['not' => $pending ?? '', 'syl' => trim(substr($line, 4))];
                    $pending = null;
                }
            }

            if ($pending !== null) {
                $lines[] = ['not' => $pending, 'syl' => ''];
            }

            if ($lines !== []) {
                $blocks[] = ['type' => 'not', 'lines' => $lines];
            }
        }

        return $blocks;
    }
}
