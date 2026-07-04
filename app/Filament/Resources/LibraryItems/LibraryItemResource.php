<?php

namespace App\Filament\Resources\LibraryItems;

use App\Filament\Resources\LibraryItems\Pages\ManageLibraryItems;
use App\Models\LibraryItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LibraryItemResource extends Resource
{
    protected static ?string $model = LibraryItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $modelLabel = 'konten';

    protected static ?string $pluralModelLabel = 'bank konten';

    protected static ?string $navigationLabel = 'Bank Lagu & Doa';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Jenis')
                ->options(LibraryItem::TYPES)
                ->default('lagu')
                ->required(),
            TextInput::make('code')
                ->label('Kode buku')
                ->placeholder('mis. PS 326, MB 401'),
            TextInput::make('title')
                ->label('Judul')
                ->required(),
            TextInput::make('set_name')
                ->label('Set ordinarium')
                ->placeholder('mis. Misa Kita 1 — untuk Tuhan Kasihanilah/Kemuliaan/Kudus/Anak Domba')
                ->helperText('Isi hanya untuk ordinarium agar mudah dipilih satu set.'),
            Repeater::make('sections')
                ->label('Bagian (refren, ayat, bait)')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama bagian')
                        ->placeholder('Refren / Ayat 1 / Bait 2'),
                    Textarea::make('notation')
                        ->label('Not angka (opsional)')
                        ->rows(4)
                        ->placeholder("not: 1 2 | 3 3 [.3] ([43]) | 5 . ||\nsyl: Sung- guh | ba- ik me- nya- | nyi. _")
                        ->helperText("Baris berpasangan not:/syl:. [43]=garis atas, [[..]]=dobel, 1'=titik atas, 1,=titik bawah, (x)=lengkung, _=tanpa suku kata."),
                    Textarea::make('body')
                        ->label('Teks / lirik')
                        ->rows(5)
                        ->helperText('Pisahkan blok tampil dengan satu baris kosong.'),
                ])
                ->defaultItems(1)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
            TagsInput::make('tags')
                ->label('Tag')
                ->placeholder('adven, natal, pembuka'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Jenis')->badge()->sortable(),
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('title')->label('Judul')->searchable()->wrap(),
                TextColumn::make('set_name')->label('Set')->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Jenis')->options(LibraryItem::TYPES),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLibraryItems::route('/'),
        ];
    }
}
