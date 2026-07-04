<?php

namespace App\Support;

use App\Models\LibraryItem;
use App\Models\Mass;
use App\Models\Theme;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Export/import seluruh konten (tema, bank, misa + rundown) sebagai JSON —
 * untuk menyiapkan misa di rumah lalu diimpor di komputer gereja.
 * Referensi bank disimpan sebagai kunci natural (type+code+title), bukan id,
 * sehingga aman diimpor ke database berbeda.
 */
class LiturgiaExchange
{
    /**
     * @param \Illuminate\Support\Collection<int, Mass>|null $masses
     *        null = export semua. Kalau diisi (bulk select), hanya misa
     *        terpilih + bank & tema yang direferensikan yang ikut.
     */
    public function export($masses = null): array
    {
        $all = $masses === null;
        $masses = Mass::with('items.libraryItem', 'theme')
            ->when(! $all, fn ($q) => $q->whereIn('id', collect($masses)->pluck('id')))
            ->get();

        $libraryItems = $all
            ? LibraryItem::all()
            : $masses->flatMap(fn (Mass $m) => $m->items->pluck('libraryItem'))
                ->filter()->unique('id')->values();

        $themes = $all
            ? Theme::all()
            : $masses->pluck('theme')->filter()->unique('id')->values();

        return [
            'app' => 'liturgia-live',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'themes' => $themes->map(fn (Theme $t) => $t->only([
                'name', 'color_key', 'accent', 'bg_tint', 'accent_style',
                'logo_path', 'background_path', 'watermark_path', 'is_default',
            ]))->all(),
            'library_items' => $libraryItems->map(fn (LibraryItem $li) => $li->only([
                'type', 'code', 'title', 'set_name', 'sections', 'tags',
            ]))->all(),
            'masses' => $masses->map(fn (Mass $m) => [
                'title' => $m->title,
                'celebrated_at' => $m->celebrated_at?->toDateTimeString(),
                'priest' => $m->priest,
                'theme_name' => $m->theme?->name,
                'is_template' => $m->is_template,
                'notes' => $m->notes,
                'items' => $m->items->map(function ($it) {
                    $li = $it->libraryItem;

                    return [
                        'sort' => $it->sort,
                        'header' => $it->header,
                        'title' => $it->title,
                        'library_ref' => $li ? ['type' => $li->type, 'code' => $li->code, 'title' => $li->title] : null,
                        'section_index' => $it->section_index,
                        'body' => $it->body,
                        'notation' => $it->notation,
                        'image_path' => $it->image_path,
                        'background_path' => $it->background_path,
                        'display' => $it->display,
                        'title_only' => $it->title_only,
                    ];
                })->all(),
            ])->all(),
        ];
    }

    /**
     * Export sebagai ZIP: data.json + semua gambar yang direferensikan
     * (logo/background/watermark tema, gambar rundown).
     * Mengembalikan path file zip sementara.
     */
    public function exportZip($masses = null): string
    {
        $data = $this->export($masses);

        $tmp = tempnam(sys_get_temp_dir(), 'liturgia').'.zip';
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file zip.');
        }

        $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $disk = Storage::disk('public');
        foreach ($this->collectFilePaths($data) as $path) {
            if ($disk->exists($path)) {
                $zip->addFile($disk->path($path), 'files/'.$path);
            }
        }

        $zip->close();

        return $tmp;
    }

    /** Import dari ZIP: ekstrak gambar ke storage publik lalu import data.json. */
    public function importZip(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \InvalidArgumentException('File zip tidak terbaca.');
        }

        $json = $zip->getFromName('data.json');
        if ($json === false) {
            $zip->close();
            throw new \InvalidArgumentException('data.json tidak ditemukan di dalam zip — bukan file export Liturgia.');
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            $zip->close();
            throw new \InvalidArgumentException('data.json di dalam zip rusak.');
        }

        $disk = Storage::disk('public');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_starts_with($name, 'files/') && ! str_ends_with($name, '/')) {
                $rel = substr($name, 6);
                if ($rel === '' || str_contains($rel, '..')) {
                    continue; // amankan dari path traversal
                }
                $disk->put($rel, (string) $zip->getFromIndex($i));
            }
        }
        $zip->close();

        return $this->import($data);
    }

    /** @return array<int, string> */
    private function collectFilePaths(array $data): array
    {
        $paths = [];
        foreach ($data['themes'] ?? [] as $t) {
            foreach (['logo_path', 'background_path', 'watermark_path'] as $key) {
                $paths[] = $t[$key] ?? null;
            }
        }
        foreach ($data['masses'] ?? [] as $m) {
            foreach ($m['items'] ?? [] as $it) {
                $paths[] = $it['image_path'] ?? null;
                $paths[] = $it['background_path'] ?? null;
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /** @return array{themes: int, library_items: int, masses: int} */
    public function import(array $data): array
    {
        if (($data['app'] ?? null) !== 'liturgia-live') {
            throw new \InvalidArgumentException('File bukan export Liturgia Live.');
        }

        $counts = ['themes' => 0, 'library_items' => 0, 'masses' => 0];

        foreach ($data['themes'] ?? [] as $t) {
            Theme::updateOrCreate(['name' => $t['name']], $t);
            $counts['themes']++;
        }

        foreach ($data['library_items'] ?? [] as $li) {
            LibraryItem::updateOrCreate(
                ['type' => $li['type'], 'code' => $li['code'] ?? null, 'title' => $li['title']],
                $li
            );
            $counts['library_items']++;
        }

        foreach ($data['masses'] ?? [] as $m) {
            $mass = Mass::updateOrCreate(
                ['title' => $m['title'], 'celebrated_at' => $m['celebrated_at'] ?? null],
                [
                    'priest' => $m['priest'] ?? null,
                    'is_template' => $m['is_template'] ?? false,
                    'notes' => $m['notes'] ?? null,
                    'theme_id' => isset($m['theme_name'])
                        ? Theme::where('name', $m['theme_name'])->value('id')
                        : null,
                ]
            );

            // Rundown diganti utuh dengan isi file (file = sumber kebenaran)
            $mass->items()->delete();
            foreach ($m['items'] ?? [] as $it) {
                $ref = $it['library_ref'] ?? null;
                unset($it['library_ref']);

                $it['library_item_id'] = $ref
                    ? LibraryItem::where('type', $ref['type'])
                        ->where('title', $ref['title'])
                        ->when($ref['code'] ?? null, fn ($q, $c) => $q->where('code', $c))
                        ->value('id')
                    : null;

                $mass->items()->create($it);
            }
            $counts['masses']++;
        }

        return $counts;
    }
}
