<?php

namespace App\Filament\Resources\Masses\Pages;

use App\Filament\Resources\Masses\MassResource;
use App\Models\Mass;
use App\Support\MisaTextImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMasses extends ListRecords
{
    protected static string $resource = MassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportAll')
                ->label('Export semua')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $zip = app(\App\Support\LiturgiaExchange::class)->exportZip();

                    return response()
                        ->download($zip, 'liturgia-semua-'.now()->format('Ymd-His').'.zip')
                        ->deleteFileAfterSend();
                }),
            Action::make('importFile')
                ->label('Import file')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('File export Liturgia (.zip atau .json)')
                        ->acceptedFileTypes([
                            'application/zip', 'application/x-zip-compressed',
                            'application/json', 'text/plain',
                        ])
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $exchange = app(\App\Support\LiturgiaExchange::class);
                    $path = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);

                    try {
                        if (str_ends_with(strtolower($path), '.zip')) {
                            $counts = $exchange->importZip($path);
                        } else {
                            $json = json_decode((string) file_get_contents($path), true);
                            if (! is_array($json)) {
                                throw new \InvalidArgumentException('File tidak terbaca.');
                            }
                            $counts = $exchange->import($json);
                        }
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    } finally {
                        \Illuminate\Support\Facades\Storage::disk('local')->delete($data['file']);
                    }

                    Notification::make()->success()
                        ->title('Import selesai')
                        ->body("{$counts['masses']} misa, {$counts['library_items']} konten bank, {$counts['themes']} tema.")
                        ->send();
                }),
            Action::make('import')
                ->label('Import dari teks')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    TextInput::make('title')
                        ->label('Nama misa')
                        ->required(),
                    DateTimePicker::make('celebrated_at')
                        ->label('Jadwal')
                        ->seconds(false),
                    TextInput::make('priest')
                        ->label('Romo selebran'),
                    Textarea::make('text')
                        ->label('Teks misa lengkap')
                        ->helperText('Paste seluruh teks (dari Word/PDF). Sistem memotong otomatis per header: LAGU PEMBUKA, TOBAT, MAZMUR TANGGAPAN, dst.')
                        ->rows(18)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $mass = Mass::create([
                        'title' => $data['title'],
                        'celebrated_at' => $data['celebrated_at'] ?? null,
                        'priest' => $data['priest'] ?? null,
                    ]);

                    $count = app(MisaTextImporter::class)->import($mass, $data['text']);

                    Notification::make()
                        ->success()
                        ->title('Import selesai')
                        ->body("{$count} item rundown dibuat. Silakan rapikan bila perlu.")
                        ->send();

                    $this->redirect(MassResource::getUrl('edit', ['record' => $mass]));
                }),
            CreateAction::make(),
        ];
    }
}
