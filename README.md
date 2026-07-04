# Liturgia Live

Sistem teks ibadah Katolik untuk live streaming dan proyektor. Dua output sekaligus (teks penuh + lower third untuk OBS), kontrol realtime dari banyak perangkat, dukungan not angka, warna liturgi otomatis per tema, dan importer yang memotong teks misa lengkap menjadi rundown secara otomatis.

Dibangun dengan Laravel 12, Filament 5, dan Laravel Reverb. Halaman output ditulis dengan JavaScript ES5 dan CSS konservatif agar kompatibel dengan browser source OBS versi lama.

## Fitur

- **Output teks penuh** (`/output/full`) — layar penuh untuk proyektor atau scene OBS: background gambar/warna tema, watermark (logo/krusifiks), aksen garis atau lingkaran, paginasi blok otomatis mengikuti tinggi layar, blok aktif di-highlight. Di browser modern, efek tambahan aktif otomatis: Ken Burns pada background, kilau aksen, teks masuk berjenjang.
- **Output lower third** (`/output/lower`) — overlay transparan untuk OBS dengan 11 preset (transparan, scrim, glass, solid, box emas, garis reveal, bertingkat, pita, pita timpa, plakat tengah, panel), transisi bertingkat ala broadcast, kilau berjalan pada elemen aksen, tinggi dijamin maksimal 1/3 layar dengan font menyusut otomatis.
- **Kontrol** (`/control`) — dibuka bersamaan dari komputer, HP, dan tablet; semua sinkron realtime via websocket. Next/prev, loncat bebas antar item dan blok (dengan cuplikan isi tiap blok), quick text, ganti mode/preset/perataan/warna/tema saat siaran. Keyboard: panah dan spasi.
- **Admin** (`/admin`, Filament) — bank lagu/doa/mazmur/ordinarium dengan bagian (refren, ayat) dan not angka; planning misa per jadwal dengan rundown masing-masing; duplikasi template mingguan; tema warna liturgi (merah, hijau, putih, ungu, pink, emas) dengan upload logo, background, dan watermark.
- **Importer teks misa** — paste teks misa lengkap (dari Word/PDF), sistem memotongnya otomatis per header liturgi (LAGU PEMBUKA, TOBAT, MAZMUR TANGGAPAN, dst).
- **Daftar lagu petugas** — paste daftar singkat seperti `pembuka 300` / `misa kita 4` / `penutup 500`; sistem mencocokkan nomor ke bank berdasarkan kode buku dan memasang satu set ordinarium sekaligus.
- **Not angka** — disimpan sebagai markup teks (bukan gambar): garis atas tunggal/dobel, titik oktaf, titik durasi, lengkung, birama; suku kata rata otomatis di bawah notnya.
- **Auto-rapi** — teks panjang tanpa baris kosong otomatis dipecah menjadi blok 2 baris (sisa 1 baris digabung menjadi 3).
- **Gambar rundown** — item bisa berisi gambar (mis. thumbnail pembukaan) yang tampil menutup layar penuh di kedua output.
- **Export/import ZIP** — pilih misa di tabel → export; file zip berisi data + semua gambar yang direferensikan. Siapkan ibadah di rumah, import di gereja.
- **Import EasyWorship** — baca `Songs.db` + `SongWords.db` langsung; lirik RTF dikonversi ke teks polos, tiap slide menjadi satu bagian lagu.

## Kebutuhan

PHP ≥ 8.2 dengan ekstensi standar Laravel (pdo, intl, gd, zip, sqlite3/mysql), Composer, dan MySQL/MariaDB atau SQLite.

## Instalasi

```bash
composer install               # otomatis menyalin .env dan membuat storage link
php artisan key:generate
# SQLite (default): touch database/database.sqlite
# MySQL: buat database lalu isi DB_* di .env
php artisan migrate --seed     # tema, template misa, contoh mazmur ber-not angka
php artisan make:filament-user # akun admin
```

## Menjalankan

```bash
php artisan serve --host=0.0.0.0 --port=8000   # web
php artisan reverb:start                        # websocket (port 8080)
```

| Halaman | URL | Untuk |
|---|---|---|
| Kontrol | `http://IP-SERVER:8000/control` | operator (komputer/HP/tablet) |
| Output lower third | `http://IP-SERVER:8000/output/lower` | browser source OBS, 1920×1080 |
| Output teks penuh | `http://IP-SERVER:8000/output/full` | proyektor / scene OBS |
| Cek kompatibilitas | `http://IP-SERVER:8000/output/check` | tes sekali di browser source |
| Admin | `http://IP-SERVER:8000/admin` | pengelolaan konten |

Aksi kontrol dilindungi PIN sederhana (`LITURGIA_PIN` di `.env`). Pastikan port 8000 dan 8080 terbuka di firewall; semua perangkat cukup berada di jaringan lokal yang sama — tidak butuh internet.

## Sintaks not angka

```
not: 1 2 | 3 3 [.3] ([43]) ([43] [46]) | 5 5 [.5] ([43]) ([42]) |
syl: Sung- guh | ba- ik me- nya- nyi- kan | syu- kur ke- pa- da- |
not: 3 . ([21]) ([[2127,]]) | 1 . . ||
syl: Mu _ ya Tu- | han. _ _
```

| Simbol | Arti |
|---|---|
| `1`–`7`, `0` | not / istirahat |
| `1'` / `1,` | titik oktaf atas / bawah |
| `.` | titik durasi |
| `[43]` / `[[..]]` | garis atas tunggal / dobel |
| `(x)` | lengkung (slur) |
| `\|` / `\|\|` | birama / penutup |
| `_` (baris syl) | tanpa suku kata |

Jumlah suku kata = jumlah token not (birama tidak dihitung). Baris kosong memisahkan blok notasi.

## Catatan konten

Repositori ini tidak menyertakan lirik lagu dari buku bernyanyi (Puji Syukur, Madah Bakti, dsb.) karena berhak cipta. Isi bank lagu dilakukan masing-masing pengguna; sekali input, lagu bisa dipanggil lewat nomor di daftar petugas.

## Kompatibilitas browser output

Halaman output menghindari fitur CSS/JS modern (`aspect-ratio`, `gap` pada flexbox, `:has()`, `backdrop-filter`, optional chaining) agar berjalan di CEF/Chromium lama pada OBS. Buka `/output/check` sekali di browser source untuk memastikan. Efek visual tambahan hanya aktif di browser modern dan otomatis nonaktif di OBS (`?fx=lite` / `?fx=rich` untuk memaksa).

## Lisensi

MIT — lihat [LICENSE](LICENSE).
