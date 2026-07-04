<?php

namespace App\Filament\Resources\Masses;

use App\Filament\Resources\Masses\Pages\CreateMass;
use App\Filament\Resources\Masses\Pages\EditMass;
use App\Filament\Resources\Masses\Pages\ListMasses;
use App\Models\LibraryItem;
use App\Models\Mass;
use App\Support\MisaTextImporter;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;

class MassResource extends Resource
{
    protected static ?string $model = Mass::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'misa';

    protected static ?string $pluralModelLabel = 'planning misa';

    protected static ?string $navigationLabel = 'Planning Misa';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Nama misa')
                ->placeholder('mis. Misa 2 — Hari Minggu Biasa XIV')
                ->required(),
            DateTimePicker::make('celebrated_at')
                ->label('Jadwal')
                ->seconds(false),
            TextInput::make('priest')
                ->label('Romo selebran')
                ->placeholder('mis. Rm. A. Surya, Pr'),
            Select::make('theme_id')
                ->label('Tema tampilan')
                ->relationship('theme', 'name')
                ->placeholder('(tema default)'),
            Toggle::make('is_template')
                ->label('Jadikan template (bisa diduplikat tiap minggu)'),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2),
            Repeater::make('items')
                ->label('Rundown')
                ->relationship()
                ->orderColumn('sort')
                ->reorderable()
                ->collapsible()
                ->collapsed()
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => trim(
                    ($state['header'] ?? '').' — '.($state['title'] ?? '')
                , ' —') ?: 'Item baru')
                ->schema([
                    TextInput::make('header')
                        ->label('Header')
                        ->placeholder('LAGU PEMBUKA / MAZMUR TANGGAPAN / ...')
                        ->datalist(MisaTextImporter::KNOWN_HEADERS),
                    TextInput::make('title')
                        ->label('Judul / konten header')
                        ->placeholder('mis. Hari Minggu Biasa XIV atau judul lagu'),
                    Select::make('library_item_id')
                        ->label('Ambil dari bank')
                        ->options(fn () => LibraryItem::query()
                            ->orderBy('title')
                            ->get()
                            ->mapWithKeys(fn (LibraryItem $li) => [
                                $li->id => '['.($li->type).'] '.$li->displayTitle(),
                            ]))
                        ->searchable()
                        ->placeholder('(kosong = teks manual di bawah)'),
                    TextInput::make('section_index')
                        ->label('Hanya bagian ke- (0=pertama, kosong=semua)')
                        ->numeric(),
                    Textarea::make('body')
                        ->label('Teks manual')
                        ->rows(4)
                        ->helperText('Pisahkan blok tampil dengan baris kosong. Baris "(Berdiri)" tampil sebagai petunjuk kecil.'),
                    Textarea::make('notation')
                        ->label('Not angka manual (opsional)')
                        ->rows(3)
                        ->placeholder("not: 1 2 | 3 3 [.3] ([43]) ||\nsyl: Sung- guh | ba- ik me- nya-"),
                    Select::make('background_path')
                        ->label('Background full layar untuk item ini (opsional)')
                        ->options(function () {
                            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('backgrounds');

                            return collect($files)
                                ->filter(fn ($f) => preg_match('/\.(svg|png|jpe?g|webp)$/i', $f))
                                ->mapWithKeys(fn ($f) => [$f => basename($f)])
                                ->all();
                        })
                        ->searchable()
                        ->placeholder('(ikut background tema)')
                        ->helperText('Galeri diambil dari storage/app/public/backgrounds — tambah gambar lewat upload background di menu Tema, atau salin file ke folder itu.'),
                    \Filament\Forms\Components\FileUpload::make('image_path')
                        ->label('Gambar full layar (opsional)')
                        ->image()
                        ->disk('public')
                        ->directory('rundown')
                        ->helperText('Mis. thumbnail YouTube untuk pembukaan. Tampil menutup layar penuh di KEDUA output (termasuk lower third). Idealnya 1920×1080.'),
                    Select::make('display')
                        ->label('Tampil di')
                        ->options([
                            'both' => 'Kedua output',
                            'full' => 'Full layar saja',
                            'lower' => 'Lower third saja',
                        ])
                        ->default('both'),
                    Toggle::make('title_only')
                        ->label('Tampilkan judul saja (mis. bacaan tanpa full text)'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Nama')->searchable()->wrap(),
                TextColumn::make('celebrated_at')->label('Jadwal')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('priest')->label('Romo'),
                TextColumn::make('theme.name')->label('Tema'),
                TextColumn::make('items_count')->label('Item')->counts('items'),
                IconColumn::make('is_template')->label('Template')->boolean(),
            ])
            ->defaultSort('celebrated_at', 'desc')
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplikat')
                    ->excludeAttributes(['is_template'])
                    ->beforeReplicaSaved(function (Mass $replica): void {
                        $replica->title = $replica->title.' (salinan)';
                        $replica->is_template = false;
                    })
                    ->after(function (Mass $replica, Mass $record): void {
                        foreach ($record->items as $item) {
                            $replica->items()->create($item->only([
                                'sort', 'header', 'title', 'library_item_id',
                                'section_index', 'body', 'notation', 'display', 'title_only',
                            ]));
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('exportSelected')
                        ->label('Export terpilih')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $zip = app(\App\Support\LiturgiaExchange::class)->exportZip($records);

                            return response()
                                ->download($zip, 'liturgia-'.now()->format('Ymd-His').'.zip')
                                ->deleteFileAfterSend();
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMasses::route('/'),
            'create' => CreateMass::route('/create'),
            'edit' => EditMass::route('/{record}/edit'),
        ];
    }
}
