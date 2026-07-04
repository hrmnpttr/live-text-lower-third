# Liturgia Live

🇮🇩 [Versi Bahasa Indonesia](README.md)

Catholic worship text system for live streaming and projectors. Two simultaneous outputs (full-screen text + lower third for OBS), realtime control from multiple devices, numbered musical notation (not angka) support, automatic liturgical colors per theme, and an importer that automatically splits a full mass text into a rundown.

Built with Laravel 12, Filament 5, and Laravel Reverb. Output pages are written in ES5 JavaScript and conservative CSS so they run on older OBS browser sources.

## Features

- **Full-screen output** (`/output/full`) — for projectors or a full OBS scene: theme background image/color, watermark (logo/crucifix), line or circle accents, automatic block pagination based on screen height, active block highlighting. In modern browsers, extra effects activate automatically: Ken Burns background, accent shimmer, staggered text entrance.
- **Lower third output** (`/output/lower`) — transparent overlay for OBS with 11 presets (transparent, gradient scrim, glass box, solid box, gold box, line reveal, staggered, ribbon, overlapping ribbon, centered nameplate, panel), broadcast-style staged transitions, animated sheen on accent elements, guaranteed maximum height of 1/3 of the screen with automatic font shrinking.
- **Control page** (`/control`) — open it on a computer, phone, and tablet at the same time; everything stays in sync via websockets. Next/prev, free jumping between items and blocks (with content previews per block), quick text, live switching of mode/preset/alignment/colors/theme. Keyboard: arrows and spacebar.
- **Admin panel** (`/admin`, Filament) — song/prayer/psalm/ordinary library with sections (refrain, verses) and notation; per-service planning with individual rundowns; weekly template duplication; liturgical color themes (red, green, white, purple, rose, gold) with logo, background, and watermark uploads.
- **Mass text importer** — paste a complete mass text (from Word/PDF) and the system splits it automatically by liturgical headers (OPENING SONG, PENITENTIAL ACT, RESPONSORIAL PSALM, etc.).
- **Song list resolver** — paste a short list like `opening 300` / `misa kita 4` / `closing 500`; the system matches numbers against hymnal codes in the library and installs a whole ordinary set at once.
- **Numbered notation (not angka)** — stored as text markup (not images): single/double beams, octave dots, duration dots, slurs, bar lines; syllables align automatically under their notes.
- **Auto-tidy** — long text without blank lines is automatically split into 2-line blocks (a leftover single line merges into 3).
- **Rundown images** — an item can contain an image (e.g., an opening thumbnail) displayed full screen on both outputs.
- **ZIP export/import** — select services in the table → export; the zip contains the data plus all referenced images. Prepare at home, import at church.
- **EasyWorship import** — reads `Songs.db` + `SongWords.db` directly; RTF lyrics are converted to plain text, each slide becomes a song section.

## Requirements

PHP ≥ 8.2 with standard Laravel extensions (pdo, intl, gd, zip, sqlite3/mysql), Composer, and MySQL/MariaDB or SQLite.

## Installation

```bash
composer install               # copies .env and creates the storage link automatically
php artisan key:generate
# SQLite (default): touch database/database.sqlite
# MySQL: create a database and fill DB_* in .env
php artisan migrate --seed     # themes, mass template, sample psalm with notation
php artisan make:filament-user # admin account
```

## Running

```bash
php artisan serve --host=0.0.0.0 --port=8000   # web
php artisan reverb:start                        # websocket (port 8080)
```

| Page | URL | For |
|---|---|---|
| Control | `http://SERVER-IP:8000/control` | operators (computer/phone/tablet) |
| Lower third output | `http://SERVER-IP:8000/output/lower` | OBS browser source, 1920×1080 |
| Full-screen output | `http://SERVER-IP:8000/output/full` | projector / OBS scene |
| Compatibility check | `http://SERVER-IP:8000/output/check` | run once in the browser source |
| Admin | `http://SERVER-IP:8000/admin` | content management |

Control actions are protected by a simple PIN (`LITURGIA_PIN` in `.env`). Open ports 8000 and 8080 in your firewall; all devices only need to be on the same local network — no internet required.

## Notation syntax

```
not: 1 2 | 3 3 [.3] ([43]) ([43] [46]) | 5 5 [.5] ([43]) ([42]) |
syl: Sung- guh | ba- ik me- nya- nyi- kan | syu- kur ke- pa- da- |
not: 3 . ([21]) ([[2127,]]) | 1 . . ||
syl: Mu _ ya Tu- | han. _ _
```

| Symbol | Meaning |
|---|---|
| `1`–`7`, `0` | note / rest |
| `1'` / `1,` | octave dot above / below |
| `.` | duration dot |
| `[43]` / `[[..]]` | single / double beam |
| `(x)` | slur |
| `\|` / `\|\|` | bar line / final bar |
| `_` (syl line) | no syllable |

Syllable count = note token count (bar lines don't count). Blank lines separate notation blocks.

## Content note

This repository does not include lyrics from copyrighted hymnals (Puji Syukur, Madah Bakti, etc.). Each user fills their own song library; once entered, songs can be recalled by number via the song list resolver.

## Output browser compatibility

Output pages avoid modern CSS/JS features (`aspect-ratio`, flexbox `gap`, `:has()`, `backdrop-filter`, optional chaining) so they run on the older CEF/Chromium embedded in OBS. Open `/output/check` once in your browser source to verify. Extra visual effects only activate in modern browsers and switch off automatically inside OBS (`?fx=lite` / `?fx=rich` to force).

## License

**Free to copy and use for non-commercial purposes** — churches, parishes, schools, communities, and personal use. You may modify and redistribute it under the same license, as long as credit is kept. Commercial use (selling, renting, or charging fees) requires written permission from the copyright holder. Full bilingual terms in [LICENSE](LICENSE).
