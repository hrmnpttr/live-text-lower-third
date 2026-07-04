<x-filament-panels::page>
    <div class="space-y-6 max-w-3xl">

        <x-filament::section>
            <x-slot name="heading">Liturgia Live v{{ \App\Filament\Pages\About::VERSION }}</x-slot>

            <div class="space-y-3 text-sm leading-relaxed">
                <p>
                    Sistem teks ibadah Katolik untuk live streaming dan proyektor —
                    output teks penuh + lower third untuk OBS, kontrol realtime dari
                    banyak perangkat, dukungan not angka, dan warna liturgi otomatis.
                </p>
                <p class="text-gray-500 dark:text-gray-400">
                    Catholic worship text system for live streaming and projectors.
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Kredit</x-slot>

            <div class="space-y-2 text-sm leading-relaxed">
                <p>
                    Dibuat oleh <strong>Ricky Cardinally Kosim</strong> &copy; 2026.
                </p>
                <p>
                    Kode sumber tersedia di
                    <a href="https://github.com/hrmnpttr/live-text-lower-third"
                       target="_blank" rel="noopener"
                       class="text-primary-600 dark:text-primary-400 underline">
                        github.com/hrmnpttr/live-text-lower-third
                    </a>
                </p>
                <p>
                    <strong>Bebas digunakan untuk keperluan non-komersial</strong> —
                    gereja, paroki, sekolah, dan komunitas. Penggunaan komersial
                    memerlukan izin tertulis. Lihat file LICENSE untuk detail lengkap.
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Donasi</x-slot>

            <div class="space-y-2 text-sm leading-relaxed">
                <p>
                    Aplikasi ini gratis dan tidak menerima donasi.
                </p>
                <p>
                    Kalau aplikasi ini membantu pelayanan Anda dan Anda ingin memberi,
                    <strong>masukkan saja ke kolekte di gereja Anda</strong>. 🙏
                </p>
                <p class="text-gray-500 dark:text-gray-400">
                    This app is free and accepts no donations. If it helps your
                    ministry and you wish to give, please put it in the collection
                    basket at your own church.
                </p>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
