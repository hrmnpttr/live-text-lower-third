<?php

namespace App\Filament\Resources\Themes;

use App\Filament\Resources\Themes\Pages\ManageThemes;
use App\Models\Theme;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $modelLabel = 'tema';

    protected static ?string $pluralModelLabel = 'tema tampilan';

    protected static ?string $navigationLabel = 'Tema & Warna Liturgi';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama tema')
                ->placeholder('mis. Adven, Masa Biasa, HUT Paroki')
                ->required(),
            Select::make('color_key')
                ->label('Warna liturgi')
                ->options([
                    'hijau' => 'Hijau — Masa Biasa',
                    'ungu' => 'Ungu — Adven / Prapaskah',
                    'putih' => 'Putih — Natal / Paskah',
                    'merah' => 'Merah — Pentakosta / Minggu Palma',
                    'pink' => 'Pink — Gaudete / Laetare',
                    'custom' => 'Custom',
                ])
                ->default('hijau')
                ->required(),
            ColorPicker::make('accent')
                ->label('Warna aksen')
                ->default('#c9b878'),
            TextInput::make('bg_tint')
                ->label('Background / tint box')
                ->helperText('Format rgba, mis. rgba(28,16,46,.92)')
                ->default('rgba(13,27,46,.92)'),
            Select::make('accent_style')
                ->label('Gaya aksen full layar')
                ->options(['garis' => 'Garis', 'bulat' => 'Bulat'])
                ->default('garis'),
            FileUpload::make('background_path')
                ->label('Background full layar')
                ->image()
                ->disk('public')
                ->directory('backgrounds')
                ->helperText('Opsional. Gambar 1920×1080; tint warna di atas tetap dipakai agar teks terbaca.'),
            FileUpload::make('watermark_path')
                ->label('Watermark (krusifiks / logo gereja)')
                ->image()
                ->disk('public')
                ->directory('watermarks')
                ->helperText('PNG transparan — mis. salib ber-corpus atau logo paroki. Tampil samar di sisi kanan layar.'),
            FileUpload::make('logo_path')
                ->label('Logo kecil (footer)')
                ->image()
                ->disk('public')
                ->directory('logos'),
            Toggle::make('is_default')
                ->label('Jadikan tema default'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('color_key')->label('Warna')->badge(),
                TextColumn::make('accent')->label('Aksen'),
                TextColumn::make('accent_style')->label('Gaya'),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageThemes::route('/'),
        ];
    }
}
