<?php

namespace App\Filament\Resources\Masses\Pages;

use App\Filament\Resources\Masses\MassResource;
use App\Models\Mass;
use App\Support\MisaTextImporter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMass extends EditRecord
{
    protected static string $resource = MassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('songList')
                ->label('Daftar lagu petugas')
                ->icon('heroicon-o-musical-note')
                ->form([
                    Textarea::make('list')
                        ->label('Daftar dari petugas')
                        ->placeholder("pembuka 300\nmazmur 815\nmisa kita 4\npersembahan 380\nkomuni 425 428\npenutup 500")
                        ->helperText('Nomor = kode Puji Syukur di bank (bisa "mb 401"). "misa kita 4" memasang satu set ordinarium. Slot yang sudah ada di rundown akan diisi, sisanya ditambahkan.')
                        ->rows(8)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Mass $mass */
                    $mass = $this->getRecord();
                    $report = app(\App\Support\SongListResolver::class)->apply($mass, $data['list']);

                    $notif = Notification::make()->title(count($report['ok']).' slot terisi');
                    if ($report['missing'] !== []) {
                        $notif->warning()->body("Belum ketemu:\n".implode("\n", $report['missing']));
                    } else {
                        $notif->success();
                    }
                    $notif->send();

                    $this->refreshFormData(['items']);
                }),
            Action::make('importAppend')
                ->label('Import teks (tambah ke rundown)')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    Textarea::make('text')
                        ->label('Teks misa / lagu')
                        ->helperText('Dipotong otomatis per header dan ditambahkan di akhir rundown.')
                        ->rows(16)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Mass $mass */
                    $mass = $this->getRecord();
                    $count = app(MisaTextImporter::class)->import($mass, $data['text']);

                    Notification::make()
                        ->success()
                        ->title("{$count} item ditambahkan")
                        ->send();

                    $this->refreshFormData(['items']);
                }),
            DeleteAction::make(),
        ];
    }
}
