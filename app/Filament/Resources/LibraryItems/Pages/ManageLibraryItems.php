<?php

namespace App\Filament\Resources\LibraryItems\Pages;

use App\Filament\Resources\LibraryItems\LibraryItemResource;
use App\Support\EasyWorshipImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageLibraryItems extends ManageRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importEasyWorship')
                ->label('Import EasyWorship')
                ->icon('heroicon-o-musical-note')
                ->form([
                    FileUpload::make('songs')
                        ->label('Songs.db')
                        ->helperText('Dari C:\Users\Public\Documents\Softouch\EasyWorship\...\Databases\Data\Songs.db')
                        ->disk('local')->directory('imports')
                        ->required(),
                    FileUpload::make('words')
                        ->label('SongWords.db')
                        ->helperText('Dari folder yang sama: SongWords.db (berisi lirik).')
                        ->disk('local')->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $songs = Storage::disk('local')->path($data['songs']);
                    $words = Storage::disk('local')->path($data['words']);

                    try {
                        $result = app(EasyWorshipImporter::class)->import($songs, $words);
                    } catch (\Throwable $e) {
                        Notification::make()->danger()
                            ->title('Import gagal')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete([$data['songs'], $data['words']]);
                    }

                    Notification::make()->success()
                        ->title("{$result['imported']} lagu diimpor")
                        ->body($result['skipped'] > 0 ? "{$result['skipped']} dilewati (kosong/tanpa lirik)." : null)
                        ->send();
                }),
            CreateAction::make()->slideOver(),
        ];
    }
}
